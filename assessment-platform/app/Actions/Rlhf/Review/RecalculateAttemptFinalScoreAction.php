<?php

namespace App\Actions\Rlhf\Review;

use App\Enums\QuestionType;
use App\Enums\RlhfReviewStatus;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizSectionQuestion;

final class RecalculateAttemptFinalScoreAction
{
    /**
     * When all RLHF answers for an attempt have been finalized, roll their
     * reviewer scores into the attempt-level `final_score` and flip the
     * review status to Completed. If any RLHF answer is still pending, the
     * attempt's `final_score` stays null.
     */
    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load([
            'answers.question',
            'answers.rlhfReview',
        ]);

        $rlhfAnswers = $attempt->answers->filter(
            fn (AttemptAnswer $answer) => $answer->question?->type === QuestionType::Rlhf,
        );

        if ($rlhfAnswers->isEmpty()) {
            return $attempt;
        }

        $allFinalized = $rlhfAnswers->every(
            fn (AttemptAnswer $answer) => $answer->rlhfReview?->finalized === true,
        );

        if (! $allFinalized) {
            return $attempt;
        }

        // Weighted score: sum(reviewer_score * maxPoints) + sum(auto_score for non-RLHF)
        // divided by total possible points, expressed as a percentage.
        $totalEarned = 0.0;
        $totalPossible = 0.0;

        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            if ($question === null) {
                continue;
            }

            $maxPoints = $this->maxPointsFor($attempt, $question);
            $totalPossible += $maxPoints;

            if ($question->type === QuestionType::Rlhf) {
                // Reviewer score is already a 0-100 scale; convert to points.
                $reviewerPercent = (float) ($answer->rlhfReview->score ?? 0);
                $totalEarned += ($reviewerPercent / 100) * $maxPoints;
            } else {
                $totalEarned += (float) ($answer->auto_score ?? 0);
            }
        }

        $finalScorePercent = $totalPossible > 0
            ? round(($totalEarned / $totalPossible) * 100, 2)
            : null;

        $attempt->update([
            'final_score' => $finalScorePercent,
            'rlhf_review_status' => RlhfReviewStatus::Completed,
        ]);

        return $attempt->refresh();
    }

    private function maxPointsFor(QuizAttempt $attempt, Question $question): float
    {
        /** @var QuizSectionQuestion|null $sectionQuestion */
        $sectionQuestion = QuizSectionQuestion::query()
            ->where('question_id', $question->id)
            ->whereHas('section', fn ($q) => $q->where('quiz_id', $attempt->quiz_id))
            ->first();

        if ($sectionQuestion?->points_override !== null) {
            return (float) $sectionQuestion->points_override;
        }

        return (float) $question->points;
    }
}
