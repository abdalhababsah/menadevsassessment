<?php

namespace App\Actions\Rlhf;

use App\Models\AttemptRlhfTurn;

final class SubmitRlhfRewriteAction
{
    public function handle(AttemptRlhfTurn $turn, string $rewrite): AttemptRlhfTurn
    {
        $turn->update([
            'selected_response_rewrite' => $rewrite,
            'rewrite_completed_at' => now(),
        ]);

        return $turn->fresh();
    }
}
