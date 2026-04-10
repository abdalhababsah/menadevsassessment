<?php

namespace App\Actions\Attempts;

use App\Models\QuizAttempt;
use App\Models\QuizSectionQuestion;
use Illuminate\Support\Facades\DB;

final class RetreatQuestionAction
{
    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->loadMissing(['quiz.sections.sectionQuestions']);

        $orderedQuestions = $attempt->quiz->sections
            ->flatMap(fn ($section) => $section->sectionQuestions)
            ->values();

        $currentIndex = $orderedQuestions->search(
            fn (QuizSectionQuestion $sectionQuestion) => $sectionQuestion->question_id === $attempt->current_question_id
        );

        if ($currentIndex === false || $currentIndex === 0) {
            return $attempt;
        }

        /** @var QuizSectionQuestion $previous */
        $previous = $orderedQuestions[$currentIndex - 1];
        $now = now();

        return DB::transaction(function () use ($attempt, $previous, $now): QuizAttempt {
            $attempt->update([
                'current_section_id' => $previous->quiz_section_id,
                'current_question_id' => $previous->question_id,
                'question_started_at' => $now,
                'section_started_at' => $attempt->current_section_id === $previous->quiz_section_id
                    ? $attempt->section_started_at
                    : $now,
            ]);

            return $attempt->fresh();
        });
    }
}
