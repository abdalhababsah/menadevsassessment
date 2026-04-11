<?php

namespace App\Actions\Rlhf;

use App\Models\AttemptRlhfTurn;
use Illuminate\Support\Facades\DB;

final class SubmitRlhfEvaluationAction
{
    /**
     * @param  array<int, array{criterion_id: int, rating_value: string, justification: string|null}>  $evaluations
     */
    public function handle(AttemptRlhfTurn $turn, string $responseSide, array $evaluations): AttemptRlhfTurn
    {
        $payloads = collect($evaluations)
            ->map(fn (array $evaluation) => [
                'rlhf_turn_id' => $turn->id,
                'criterion_id' => $evaluation['criterion_id'],
                'response_side' => $responseSide,
                'rating_value' => $evaluation['rating_value'],
                'justification' => $evaluation['justification'],
            ])
            ->values()
            ->all();

        DB::transaction(function () use ($turn, $payloads): void {
            if ($payloads === []) {
                return;
            }

            $turn->evaluations()->upsert(
                $payloads,
                ['rlhf_turn_id', 'criterion_id', 'response_side'],
                ['rating_value', 'justification']
            );
        });

        return $turn->fresh(['evaluations']);
    }
}
