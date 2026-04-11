<?php

namespace App\Actions\Rlhf\Review;

use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfReview;
use Illuminate\Support\Facades\Auth;

final class SubmitRlhfReviewAction
{
    /**
     * Save a draft review for an RLHF answer. Returns the review record.
     *
     * @param  array{score: float, decision: string, comments: ?string}  $data
     */
    public function handle(AttemptAnswer $answer, array $data): AttemptRlhfReview
    {
        /** @var AttemptRlhfReview $review */
        $review = AttemptRlhfReview::query()->updateOrCreate(
            ['attempt_answer_id' => $answer->id],
            [
                'reviewer_id' => Auth::id(),
                'score' => $data['score'],
                'decision' => $data['decision'],
                'comments' => $data['comments'] ?? null,
                // Do not flip `finalized` here — that's a separate action.
            ],
        );

        $answer->update([
            'reviewer_score' => $data['score'],
        ]);

        return $review;
    }
}
