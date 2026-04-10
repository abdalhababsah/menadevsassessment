<?php

namespace App\Actions\Quizzes;

use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class ReorderSectionQuestionsAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, int>  $orderedSectionQuestionIds  Pivot IDs in new order, all belonging to $section.
     */
    public function handle(QuizSection $section, array $orderedSectionQuestionIds): void
    {
        DB::transaction(function () use ($section, $orderedSectionQuestionIds): void {
            foreach ($orderedSectionQuestionIds as $position => $pivotId) {
                QuizSectionQuestion::where('quiz_section_id', $section->id)
                    ->where('id', $pivotId)
                    ->update(['position' => $position]);
            }
        });

        $this->audit->log('quiz.section_questions_reordered', $section, [
            'order' => $orderedSectionQuestionIds,
        ]);
    }
}
