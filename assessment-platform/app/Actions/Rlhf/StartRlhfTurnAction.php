<?php

namespace App\Actions\Rlhf;

use App\Enums\RlhfTurnGenerationStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfTurn;
use InvalidArgumentException;

final class StartRlhfTurnAction
{
    public function handle(AttemptAnswer $answer): AttemptRlhfTurn
    {
        $answer->loadMissing(['question.rlhfConfig', 'rlhfTurns']);

        $config = $answer->question?->rlhfConfig;

        if ($config === null) {
            throw new InvalidArgumentException('RLHF configuration is missing for this question.');
        }

        /** @var AttemptRlhfTurn|null $activeTurn */
        $activeTurn = $answer->rlhfTurns
            ->whereNull('completed_at')
            ->sortBy('turn_number')
            ->first();

        if ($activeTurn !== null) {
            return $activeTurn;
        }

        $nextTurnNumber = ((int) $answer->rlhfTurns->max('turn_number')) + 1;

        if ($nextTurnNumber > $config->number_of_turns) {
            throw new InvalidArgumentException('All RLHF turns have already been started.');
        }

        return AttemptRlhfTurn::create([
            'attempt_answer_id' => $answer->id,
            'turn_number' => $nextTurnNumber,
            'model_a' => $config->model_a,
            'model_b' => $config->model_b,
            'generation_status' => RlhfTurnGenerationStatus::Pending,
        ]);
    }
}
