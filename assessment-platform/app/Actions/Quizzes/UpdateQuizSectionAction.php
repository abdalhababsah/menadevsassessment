<?php

namespace App\Actions\Quizzes;

use App\Models\QuizSection;
use App\Services\AuditLogger;

final class UpdateQuizSectionAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(QuizSection $section, string $title, ?string $description, ?int $timeLimitSeconds): QuizSection
    {
        $section->update([
            'title' => $title,
            'description' => $description,
            'time_limit_seconds' => $timeLimitSeconds,
        ]);

        $this->audit->log('quiz.section_updated', $section, [
            'title' => $title,
        ]);

        return $section->refresh();
    }
}
