<?php

namespace App\Actions\Quizzes;

use App\Models\QuizSection;
use App\Services\AuditLogger;

final class DeleteQuizSectionAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(QuizSection $section): void
    {
        $this->audit->log('quiz.section_deleted', $section, [
            'quiz_id' => $section->quiz_id,
            'title' => $section->title,
        ]);

        $section->delete();
    }
}
