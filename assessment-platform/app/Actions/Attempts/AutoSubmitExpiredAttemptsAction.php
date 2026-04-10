<?php

namespace App\Actions\Attempts;

use App\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use App\Services\Scoring\QuizScoringService;
use Illuminate\Support\Carbon;

final class AutoSubmitExpiredAttemptsAction
{
    public function __construct(
        private QuizScoringService $scoring,
    ) {}

    /**
     * Auto-submit any in-progress attempt that has exceeded the quiz time limit.
     *
     * @return int number of attempts auto-submitted
     */
    public function handle(): int
    {
        $count = 0;

        QuizAttempt::query()
            ->where('status', AttemptStatus::InProgress)
            ->with('quiz')
            ->chunk(100, function ($attempts) use (&$count) {
                foreach ($attempts as $attempt) {
                    /** @var QuizAttempt $attempt */
                    $limitSeconds = $attempt->quiz?->time_limit_seconds;
                    if ($limitSeconds === null) {
                        continue;
                    }

                    $started = $attempt->started_at ?? Carbon::now();
                    if ($started->copy()->addSeconds($limitSeconds)->isPast()) {
                        $this->scoring->markAutoSubmitted($attempt);
                        $count++;
                    }
                }
            });

        return $count;
    }
}
