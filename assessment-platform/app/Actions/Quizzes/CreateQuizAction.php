<?php

namespace App\Actions\Quizzes;

use App\Enums\QuizNavigationMode;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AuditLogger;

final class CreateQuizAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(string $title, ?string $description, User $creator): Quiz
    {
        $quiz = Quiz::create([
            'title' => $title,
            'description' => $description,
            'navigation_mode' => QuizNavigationMode::Free,
            'status' => QuizStatus::Draft,
            'created_by' => $creator->id,
        ]);

        $this->audit->log('quiz.created', $quiz, [
            'title' => $title,
        ]);

        return $quiz;
    }
}
