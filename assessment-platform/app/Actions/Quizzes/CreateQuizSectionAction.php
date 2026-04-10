<?php

namespace App\Actions\Quizzes;

use App\Models\Quiz;
use App\Models\QuizSection;
use App\Services\AuditLogger;

final class CreateQuizSectionAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(Quiz $quiz, string $title, ?string $description, ?int $timeLimitSeconds): QuizSection
    {
        $position = (int) ($quiz->sections()->max('position') ?? -1) + 1;

        /** @var QuizSection $section */
        $section = $quiz->sections()->create([
            'title' => $title,
            'description' => $description,
            'time_limit_seconds' => $timeLimitSeconds,
            'position' => $position,
        ]);

        $this->audit->log('quiz.section_created', $section, [
            'quiz_id' => $quiz->id,
            'title' => $title,
        ]);

        return $section;
    }
}
