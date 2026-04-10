<?php

namespace App\Actions\Attempts;

use App\Models\QuizAttempt;
use App\Models\QuizSectionQuestion;
use Illuminate\Support\Facades\DB;

final class AdvanceQuestionAction
{
    /**
        * Move the attempt cursor to the next question in order.
        * Returns the updated attempt; if there is no next question, the cursor stays on the last one.
        */
    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->loadMissing(['quiz.sections.sectionQuestions']);

        $sections = $attempt->quiz->sections;
        if ($sections->isEmpty()) {
            return $attempt;
        }

        $currentSectionIndex = $sections->search(fn ($section) => $section->id === $attempt->current_section_id);
        $currentSectionIndex = $currentSectionIndex === false ? 0 : $currentSectionIndex;
        $currentSection = $sections[$currentSectionIndex];

        $sectionQuestions = $currentSection->sectionQuestions;
        $currentQuestionIndex = $sectionQuestions->search(fn (QuizSectionQuestion $sq) => $sq->question_id === $attempt->current_question_id);
        $currentQuestionIndex = $currentQuestionIndex === false ? 0 : $currentQuestionIndex;

        $nextSectionIndex = $currentSectionIndex;
        $nextQuestionId = null;

        // Next question in the same section?
        if ($sectionQuestions->count() > $currentQuestionIndex + 1) {
            $nextQuestionId = (int) $sectionQuestions[$currentQuestionIndex + 1]->question_id;
        } elseif ($sections->count() > $currentSectionIndex + 1) {
            // Move to the first question of the next section.
            $nextSectionIndex = $currentSectionIndex + 1;
            $nextSection = $sections[$nextSectionIndex];
            $nextSectionQuestion = $nextSection->sectionQuestions->first();
            if ($nextSectionQuestion !== null) {
                $nextQuestionId = (int) $nextSectionQuestion->question_id;
            }
        }

        if ($nextQuestionId === null) {
            return $attempt;
        }

        $nextSectionId = $sections[$nextSectionIndex]->id;
        $now = now();

        return DB::transaction(function () use ($attempt, $nextSectionId, $nextQuestionId, $now): QuizAttempt {
            $attempt->update([
                'current_section_id' => $nextSectionId,
                'current_question_id' => $nextQuestionId,
                'question_started_at' => $now,
                'section_started_at' => $attempt->current_section_id === $nextSectionId
                    ? $attempt->section_started_at
                    : $now,
            ]);

            return $attempt->fresh();
        });
    }
}
