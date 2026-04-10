<?php

namespace App\Actions\Quizzes;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Services\AuditLogger;

final class UnpublishQuizAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function handle(Quiz $quiz): Quiz
    {
        $quiz->update(['status' => QuizStatus::Draft]);

        $this->audit->log('quiz.unpublished', $quiz);

        return $quiz->refresh();
    }
}
