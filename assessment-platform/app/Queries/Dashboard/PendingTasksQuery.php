<?php

namespace App\Queries\Dashboard;

use App\Enums\RlhfReviewStatus;
use App\Models\QuizAttempt;
use App\Models\AttemptSuspiciousEvent;

class PendingTasksQuery
{
    public function execute(): array
    {
        return [
            'rlhf_reviews' => QuizAttempt::where('rlhf_review_status', RlhfReviewStatus::Pending)
                ->count(),
            // Assuming coding verification is handled via a status or specific check
            // For now, we'll simulate or use a logical placeholder
            'coding_verifications' => 0, 
            'suspicious_attempts' => AttemptSuspiciousEvent::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->distinct('quiz_attempt_id')
                ->count(),
        ];
    }
}
