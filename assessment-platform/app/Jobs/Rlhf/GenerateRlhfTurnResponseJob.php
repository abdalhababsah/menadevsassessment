<?php

namespace App\Jobs\Rlhf;

use App\Contracts\AiProviders\AiResponseGenerator;
use App\Enums\RlhfTurnGenerationStatus;
use App\Exceptions\AiRateLimitException;
use App\Models\AttemptRlhfTurn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class GenerateRlhfTurnResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 90;

    public function __construct(
        public int $turnId,
        public string $side,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function handle(AiResponseGenerator $generator): void
    {
        $turn = AttemptRlhfTurn::query()
            ->with(['answer.question.rlhfConfig', 'answer.question', 'answer.rlhfTurns'])
            ->find($this->turnId);

        if ($turn === null) {
            return;
        }

        if ($this->responseForSide($turn) !== null) {
            $this->markJobReady($turn);

            return;
        }

        try {
            Redis::funnel('anthropic-api')->limit(40)->then(
                fn () => $this->generate($turn, $generator),
                function (): void {
                    $this->release($this->nextBackoffDelay());
                }
            );
        } catch (Throwable) {
            // Local test environments may not have Redis available. In that
            // case, fall back to direct execution while preserving the job's
            // retry/backoff behavior for provider-side failures.
            $this->generate($turn, $generator);
        }
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function buildMessages(AttemptRlhfTurn $turn): array
    {
        $turn->loadMissing(['answer.question.rlhfConfig', 'answer.question', 'answer.rlhfTurns']);

        $question = $turn->answer->question;
        $config = $question->rlhfConfig;

        $systemParts = array_filter([
            $question->stem,
            $question->instructions,
            $config?->guidelines_markdown,
        ]);

        $messages = [];

        if ($systemParts !== []) {
            $messages[] = [
                'role' => 'system',
                'content' => implode("\n\n", $systemParts),
            ];
        }

        foreach ($turn->priorTurns() as $priorTurn) {
            $selectedResponse = $priorTurn->selectedResponseText();

            if ($selectedResponse !== null) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $selectedResponse,
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => (string) $turn->candidate_input,
        ];

        return $messages;
    }

    public function failed(?Throwable $exception): void
    {
        $turn = AttemptRlhfTurn::query()->find($this->turnId);

        if ($turn === null) {
            return;
        }

        $turn->generationJobs()
            ->where('side', $this->side)
            ->update([
                'status' => RlhfTurnGenerationStatus::Failed,
                'attempts' => $this->attempts(),
                'last_error' => $exception?->getMessage(),
                'finished_at' => now(),
            ]);

        $turn->update([
            'generation_status' => RlhfTurnGenerationStatus::Failed,
            'generation_error' => $exception?->getMessage(),
        ]);
    }

    private function generate(AttemptRlhfTurn $turn, AiResponseGenerator $generator): void
    {
        $turn->generationJobs()
            ->where('side', $this->side)
            ->update([
                'status' => RlhfTurnGenerationStatus::Generating,
                'attempts' => $this->attempts(),
                'last_error' => null,
                'started_at' => now(),
            ]);

        try {
            $response = $generator->generate(
                $this->buildMessages($turn),
                $this->modelForSide($turn),
                $turn->answer->question->rlhfConfig?->generation_params ?? []
            );
        } catch (AiRateLimitException $exception) {
            $turn->generationJobs()
                ->where('side', $this->side)
                ->update([
                    'status' => RlhfTurnGenerationStatus::Pending,
                    'attempts' => $this->attempts(),
                    'last_error' => $exception->getMessage(),
                ]);

            throw $exception;
        }

        $turn->update([
            $this->responseColumn() => $response->content,
        ]);

        $turn->generationJobs()
            ->where('side', $this->side)
            ->update([
                'status' => RlhfTurnGenerationStatus::Ready,
                'attempts' => $this->attempts(),
                'last_error' => null,
                'finished_at' => now(),
            ]);

        $turn->refresh();
        $turn->update([
            'generation_status' => $turn->bothResponsesReady()
                ? RlhfTurnGenerationStatus::Ready
                : RlhfTurnGenerationStatus::Generating,
            'generation_error' => null,
            'generated_at' => $turn->bothResponsesReady() ? now() : $turn->generated_at,
        ]);
    }

    private function responseForSide(AttemptRlhfTurn $turn): ?string
    {
        return $this->side === 'a' ? $turn->response_a : $turn->response_b;
    }

    private function responseColumn(): string
    {
        return $this->side === 'a' ? 'response_a' : 'response_b';
    }

    private function modelForSide(AttemptRlhfTurn $turn): string
    {
        return $this->side === 'a' ? $turn->model_a : $turn->model_b;
    }

    private function nextBackoffDelay(): int
    {
        return $this->backoff()[$this->attempts() - 1] ?? 120;
    }

    private function markJobReady(AttemptRlhfTurn $turn): void
    {
        $turn->generationJobs()
            ->where('side', $this->side)
            ->update([
                'status' => RlhfTurnGenerationStatus::Ready,
                'finished_at' => now(),
            ]);
    }
}
