<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use App\Services\AuditLogger;
use App\Services\Scoring\QuizScoringService;
use Illuminate\Support\Facades\DB;

final class LockOrAutoSubmitAttemptAction
{
    public function __construct(
        private QuizScoringService $scoring,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Lock or auto-submit an in-progress attempt.
     *
     * Locks the attempt when anti-cheat thresholds are exceeded.
     * If the quiz has no time-pressure context (i.e. purely locked for proctoring),
     * we mark it AutoSubmitted so scoring still runs and the candidate cannot continue.
     */
    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        $locked = DB::transaction(function () use ($attempt): QuizAttempt {
            $attempt->update([
                'status' => AttemptStatus::AutoSubmitted,
                'submitted_at' => now(),
            ]);

            return $this->scoring->markAutoSubmitted($attempt->fresh(['answers.question']));
        });

        $this->auditLogger->log('quiz.attempt_locked', $locked, [
            'quiz_id' => $locked->quiz_id,
            'candidate_id' => $locked->candidate_id,
            'reason' => 'anti_cheat_threshold_exceeded',
        ]);

        return $locked;
    }
}
