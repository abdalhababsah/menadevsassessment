<?php

namespace App\Services\Scoring;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Enums\RlhfReviewStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptCodingSubmission;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizSectionQuestion;

final class QuizScoringService
{
    /**
     * Recompute auto_score for every answer in the attempt and roll up
     * an attempt-level score. RLHF answers stay null and the attempt's
     * rlhf_review_status flips to Pending if any RLHF answers exist.
     */
    public function recalculate(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load(['answers.question']);

        $totalEarned = 0.0;
        $totalPossible = 0.0;
        $hasRlhf = false;

        foreach ($attempt->answers as $answer) {
            /** @var AttemptAnswer $answer */
            $question = $answer->question;
            if ($question === null) {
                continue;
            }

            $maxPoints = $this->maxPointsFor($attempt, $answer, $question);

            $earned = match ($question->type) {
                QuestionType::SingleSelect => $this->scoreSingleSelect($answer, $maxPoints),
                QuestionType::MultiSelect => $this->scoreMultiSelect($answer, $maxPoints),
                QuestionType::Coding => $this->scoreCoding($answer, $maxPoints),
                QuestionType::Rlhf => null,
            };

            if ($question->type === QuestionType::Rlhf) {
                $hasRlhf = true;
                continue;
            }

            $answer->update(['auto_score' => $earned]);

            $totalEarned += (float) $earned;
            $totalPossible += $maxPoints;
        }

        $autoScorePercent = $totalPossible > 0
            ? round(($totalEarned / $totalPossible) * 100, 2)
            : null;

        $attempt->update([
            'auto_score' => $autoScorePercent,
            'final_score' => $hasRlhf ? null : $autoScorePercent,
            'rlhf_review_status' => $hasRlhf ? RlhfReviewStatus::Pending : RlhfReviewStatus::NotRequired,
        ]);

        return $attempt->refresh();
    }

    private function maxPointsFor(QuizAttempt $attempt, AttemptAnswer $answer, Question $question): float
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

    private function scoreSingleSelect(AttemptAnswer $answer, float $maxPoints): float
    {
        $answer->loadMissing(['selections.option', 'question.options']);

        if ($answer->selections->isEmpty()) {
            return 0.0;
        }

        $selectedId = $answer->selections->first()->question_option_id;
        $correctOption = $answer->question->options->where('is_correct', true)->first();

        if ($correctOption === null) {
            return 0.0;
        }

        return $selectedId === $correctOption->id ? $maxPoints : 0.0;
    }

    private function scoreMultiSelect(AttemptAnswer $answer, float $maxPoints): float
    {
        $answer->loadMissing(['selections', 'question.options']);

        $selectedIds = $answer->selections->pluck('question_option_id')->sort()->values()->all();
        $correctIds = $answer->question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        if (empty($correctIds)) {
            return 0.0;
        }

        // All-or-nothing scoring: full points only when the candidate selects
        // exactly the correct set with no extras.
        return $selectedIds === $correctIds ? $maxPoints : 0.0;
    }

    private function scoreCoding(AttemptAnswer $answer, float $maxPoints): float
    {
        $answer->loadMissing(['codingSubmission.testResults']);

        /** @var AttemptCodingSubmission|null $submission */
        $submission = $answer->codingSubmission;

        if ($submission === null || $submission->testResults->isEmpty()) {
            return 0.0;
        }

        $totalWeight = 0.0;
        $earnedWeight = 0.0;

        foreach ($submission->testResults as $result) {
            $weight = 1.0;
            $testCase = $result->testCase;
            if ($testCase !== null) {
                $weight = (float) $testCase->weight;
            }

            $totalWeight += $weight;
            if ($result->passed) {
                $earnedWeight += $weight;
            }
        }

        if ($totalWeight <= 0) {
            return 0.0;
        }

        return round(($earnedWeight / $totalWeight) * $maxPoints, 2);
    }

    /**
     * Mark the attempt as auto-submitted (used by the timer expiry path).
     */
    public function markAutoSubmitted(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->isComplete()) {
            return $attempt;
        }

        $attempt->update([
            'status' => AttemptStatus::AutoSubmitted,
            'submitted_at' => now(),
        ]);

        return $this->recalculate($attempt);
    }
}
