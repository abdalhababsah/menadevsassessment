<?php

namespace Database\Factories;

use App\Enums\SuspiciousEventType;
use App\Models\AttemptSuspiciousEvent;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptSuspiciousEvent>
 */
class AttemptSuspiciousEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'event_type' => fake()->randomElement(SuspiciousEventType::cases()),
            'occurred_at' => now(),
            'metadata' => null,
        ];
    }
}
