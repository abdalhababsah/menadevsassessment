<?php

namespace App\Actions\Rlhf;

use App\Enums\SelectedSide;
use App\Models\AttemptRlhfTurn;

final class SubmitRlhfSxsRatingAction
{
    public function handle(
        AttemptRlhfTurn $turn,
        int $rating,
        string $justification,
        ?SelectedSide $selectedSide = null,
    ): AttemptRlhfTurn {
        $derivedSide = $selectedSide ?? match (true) {
            $rating < 4 => SelectedSide::A,
            $rating > 4 => SelectedSide::B,
            default => null,
        };

        $turn->update([
            'sxs_rating' => $rating,
            'sxs_justification' => $justification,
            'selected_side' => $derivedSide,
        ]);

        return $turn->fresh();
    }
}
