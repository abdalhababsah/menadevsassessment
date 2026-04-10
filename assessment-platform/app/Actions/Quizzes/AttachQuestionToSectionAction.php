<?php

namespace App\Actions\Quizzes;

use App\Models\Question;
use App\Models\QuizSection;
use App\Models\QuizSectionQuestion;
use App\Services\AuditLogger;

final class AttachQuestionToSectionAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(
        QuizSection $section,
        Question $question,
        ?float $pointsOverride = null,
        ?int $timeLimitOverrideSeconds = null,
    ): QuizSectionQuestion {
        $position = (int) ($section->sectionQuestions()->max('position') ?? -1) + 1;

        /** @var QuizSectionQuestion $sectionQuestion */
        $sectionQuestion = $section->sectionQuestions()->create([
            'question_id' => $question->id,
            'question_version' => $question->version,
            'points_override' => $pointsOverride,
            'time_limit_override_seconds' => $timeLimitOverrideSeconds,
            'position' => $position,
        ]);

        $this->audit->log('quiz.question_attached', $section, [
            'question_id' => $question->id,
            'question_version' => $question->version,
        ]);

        return $sectionQuestion;
    }
}
