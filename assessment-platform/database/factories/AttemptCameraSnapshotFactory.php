<?php

namespace Database\Factories;

use App\Models\AttemptCameraSnapshot;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptCameraSnapshot>
 */
class AttemptCameraSnapshotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'url' => fake()->imageUrl(),
            'captured_at' => now(),
            'flagged' => false,
        ];
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'flagged' => true,
        ]);
    }
}
