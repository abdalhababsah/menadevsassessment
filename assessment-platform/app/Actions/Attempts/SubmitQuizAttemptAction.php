<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use App\Services\Scoring\QuizScoringService;
use Illuminate\Support\Facades\DB;

final class SubmitQuizAttemptAction
{
    public function __construct(
        private QuizScoringService $scoring,
    ) {}

    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->isComplete()) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt): QuizAttempt {
            $attempt->update([
                'status' => AttemptStatus::Submitted,
                'submitted_at' => now(),
            ]);

            return $this->scoring->recalculate($attempt->fresh(['answers.question']));
        });
    }
}
