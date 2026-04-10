<?php

namespace App\Actions\Quizzes;

use App\Models\QuizSectionQuestion;
use App\Services\AuditLogger;

final class DetachQuestionFromSectionAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(QuizSectionQuestion $sectionQuestion): void
    {
        $this->audit->log('quiz.question_detached', $sectionQuestion->section, [
            'question_id' => $sectionQuestion->question_id,
            'pivot_id' => $sectionQuestion->id,
        ]);

        $sectionQuestion->delete();
    }
}
