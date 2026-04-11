<?php

namespace App\Actions\Rlhf;

use App\Enums\AnswerStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfTurn;
use Illuminate\Support\Facades\DB;

final class AdvanceRlhfTurnAction
{
    public function __construct(
        private StartRlhfTurnAction $startTurn,
    ) {}

    public function handle(AttemptRlhfTurn $turn): ?AttemptRlhfTurn
    {
        $turn->loadMissing(['answer.question.rlhfConfig', 'answer.rlhfTurns']);

        /** @var AttemptAnswer $answer */
        $answer = $turn->answer;
        $config = $answer->question->rlhfConfig;

        return DB::transaction(function () use ($turn, $answer, $config): ?AttemptRlhfTurn {
            $turn->update([
                'completed_at' => $turn->completed_at ?? now(),
            ]);

            if ($turn->turn_number >= $config->number_of_turns) {
                $answer->update([
                    'status' => AnswerStatus::Answered,
                    'answered_at' => now(),
                ]);

                return null;
            }

            $answer->unsetRelation('rlhfTurns');

            return $this->startTurn->handle($answer);
        });
    }
}
