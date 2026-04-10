<?php

namespace App\Actions\Quizzes;

use App\Models\Quiz;
use App\Models\QuizSection;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class ReorderQuizSectionsAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, int>  $orderedSectionIds  IDs in their new order, all belonging to $quiz.
     */
    public function handle(Quiz $quiz, array $orderedSectionIds): void
    {
        DB::transaction(function () use ($quiz, $orderedSectionIds): void {
            foreach ($orderedSectionIds as $position => $sectionId) {
                QuizSection::where('quiz_id', $quiz->id)
                    ->where('id', $sectionId)
                    ->update(['position' => $position]);
            }
        });

        $this->audit->log('quiz.sections_reordered', $quiz, [
            'order' => $orderedSectionIds,
        ]);
    }
}
