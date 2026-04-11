<?php

namespace App\Actions\Rlhf;

use App\Enums\RlhfTurnGenerationStatus;
use App\Jobs\Rlhf\GenerateRlhfTurnResponseJob;
use App\Models\AttemptRlhfTurn;
use Illuminate\Support\Facades\DB;

final class SubmitRlhfPromptInputAction
{
    public function handle(AttemptRlhfTurn $turn, string $input, ?string $audioUrl = null): AttemptRlhfTurn
    {
        return DB::transaction(function () use ($turn, $input, $audioUrl): AttemptRlhfTurn {
            $turn->update([
                'candidate_input' => $input,
                'candidate_input_audio_url' => $audioUrl,
                'response_a' => null,
                'response_b' => null,
                'generation_status' => RlhfTurnGenerationStatus::Generating,
                'generation_error' => null,
                'generated_at' => null,
            ]);

            $payloads = [
                [
                    'rlhf_turn_id' => $turn->id,
                    'side' => 'a',
                    'status' => RlhfTurnGenerationStatus::Pending,
                    'attempts' => 0,
                    'last_error' => null,
                    'started_at' => null,
                    'finished_at' => null,
                ],
                [
                    'rlhf_turn_id' => $turn->id,
                    'side' => 'b',
                    'status' => RlhfTurnGenerationStatus::Pending,
                    'attempts' => 0,
                    'last_error' => null,
                    'started_at' => null,
                    'finished_at' => null,
                ],
            ];

            $turn->generationJobs()->upsert(
                $payloads,
                ['rlhf_turn_id', 'side'],
                ['status', 'attempts', 'last_error', 'started_at', 'finished_at']
            );

            DB::afterCommit(function () use ($turn): void {
                GenerateRlhfTurnResponseJob::dispatch($turn->id, 'a');
                GenerateRlhfTurnResponseJob::dispatch($turn->id, 'b');
            });

            return $turn->fresh(['generationJobs']);
        });
    }
}
