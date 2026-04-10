<?php

namespace App\Actions\Quizzes;

use App\Models\Quiz;
use App\Services\AuditLogger;

final class DeleteQuizAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(Quiz $quiz): void
    {
        $this->audit->log('quiz.deleted', $quiz, [
            'title' => $quiz->title,
        ]);

        $quiz->delete();
    }
}
