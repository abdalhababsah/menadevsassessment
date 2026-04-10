<?php

namespace App\Actions\Quizzes;

use App\Models\QuizSectionQuestion;
use App\Services\AuditLogger;

final class UpdateSectionQuestionPivotAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(
        QuizSectionQuestion $sectionQuestion,
        ?float $pointsOverride,
        ?int $timeLimitOverrideSeconds,
    ): QuizSectionQuestion {
        $sectionQuestion->update([
            'points_override' => $pointsOverride,
            'time_limit_override_seconds' => $timeLimitOverrideSeconds,
        ]);

        $this->audit->log('quiz.section_question_pivot_updated', $sectionQuestion->section, [
            'pivot_id' => $sectionQuestion->id,
        ]);

        return $sectionQuestion->refresh();
    }
}
