<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Events\QuizAttemptSubmitted;
use App\Models\QuizAttempt;
use App\Services\AuditLogger;
use App\Services\Scoring\QuizScoringService;
use Illuminate\Support\Facades\DB;

final class SubmitQuizAttemptAction
{
    public function __construct(
        private QuizScoringService $scoring,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->isComplete()) {
            return $attempt;
        }

        $scored = DB::transaction(function () use ($attempt): QuizAttempt {
            $attempt->update([
                'status' => AttemptStatus::Submitted,
                'submitted_at' => now(),
            ]);

            return $this->scoring->recalculate($attempt->fresh(['answers.question']));
        });

        $this->auditLogger->log('quiz.attempt_submitted', $scored, [
            'quiz_id' => $scored->quiz_id,
            'candidate_id' => $scored->candidate_id,
            'auto_score' => $scored->auto_score !== null ? (float) $scored->auto_score : null,
        ]);

        QuizAttemptSubmitted::dispatch($scored);

        return $scored;
    }
}
