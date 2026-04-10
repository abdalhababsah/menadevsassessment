<?php

namespace App\Actions\Attempts;

use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

final class AdvanceSectionAction
{
    /**
     * Jump to the next section's first question.
     */
    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->loadMissing(['quiz.sections.sectionQuestions']);

        $sections = $attempt->quiz->sections;
        if ($sections->isEmpty()) {
            return $attempt;
        }

        $currentIndex = $sections->search(fn ($section) => $section->id === $attempt->current_section_id);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;

        if ($sections->count() <= $currentIndex + 1) {
            return $attempt;
        }

        $nextSection = $sections[$currentIndex + 1];
        $nextQuestion = $nextSection->sectionQuestions->first();

        if ($nextQuestion === null) {
            return $attempt;
        }

        $now = now();

        return DB::transaction(function () use ($attempt, $nextSection, $nextQuestion, $now): QuizAttempt {
            $attempt->update([
                'current_section_id' => $nextSection->id,
                'current_question_id' => $nextQuestion->question_id,
                'section_started_at' => $now,
                'question_started_at' => $now,
            ]);

            return $attempt->fresh();
        });
    }
}
