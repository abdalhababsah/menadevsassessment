<?php

namespace App\Actions\Rlhf\Review;

use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfReview;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class FinalizeRlhfReviewAction
{
    public function __construct(
        private RecalculateAttemptFinalScoreAction $recalc,
    ) {}

    /**
     * Lock the review on a single RLHF answer and trigger attempt-level
     * recalculation. Returns the finalized review.
     */
    public function handle(AttemptAnswer $answer): AttemptRlhfReview
    {
        return DB::transaction(function () use ($answer): AttemptRlhfReview {
            /** @var AttemptRlhfReview|null $review */
            $review = AttemptRlhfReview::query()
                ->where('attempt_answer_id', $answer->id)
                ->first();

            if ($review === null) {
                throw new RuntimeException('Cannot finalize a review that has not been saved yet.');
            }

            $review->update(['finalized' => true]);

            $answer->update([
                'reviewer_score' => $review->score,
            ]);

            $this->recalc->handle($answer->attempt);

            return $review->refresh();
        });
    }
}
