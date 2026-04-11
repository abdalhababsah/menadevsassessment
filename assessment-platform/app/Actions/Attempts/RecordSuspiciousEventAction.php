<?php

namespace App\Actions\Attempts;

use App\Enums\SuspiciousEventType;
use App\Models\AttemptSuspiciousEvent;
use App\Models\QuizAttempt;

final class RecordSuspiciousEventAction
{
    public function __construct(
        private LockOrAutoSubmitAttemptAction $lockAction,
    ) {}

    /**
     * Record a suspicious event and enforce fullscreen-exit lock threshold.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(
        QuizAttempt $attempt,
        SuspiciousEventType $eventType,
        ?array $metadata = null,
    ): AttemptSuspiciousEvent {
        $event = AttemptSuspiciousEvent::create([
            'quiz_attempt_id' => $attempt->id,
            'event_type' => $eventType,
            'occurred_at' => now(),
            'metadata' => $metadata,
        ]);

        if ($eventType === SuspiciousEventType::FullscreenExit) {
            $this->checkFullscreenExitThreshold($attempt);
        }

        return $event;
    }

    private function checkFullscreenExitThreshold(QuizAttempt $attempt): void
    {
        $attempt->loadMissing('quiz');

        $max = $attempt->quiz?->max_fullscreen_exits ?? 0;

        if ($max < 1) {
            return;
        }

        $exitCount = $attempt->suspiciousEvents()
            ->where('event_type', SuspiciousEventType::FullscreenExit)
            ->count();

        if ($exitCount >= $max) {
            $this->lockAction->handle($attempt);
        }
    }
}
