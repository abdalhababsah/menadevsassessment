<?php

namespace Database\Factories;

use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptRlhfReview>
 */
class AttemptRlhfReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_answer_id' => AttemptAnswer::factory(),
            'reviewer_id' => User::factory(),
            'score' => fake()->randomFloat(2, 0, 100),
            'decision' => fake()->randomElement(['accept', 'reject', 'needs_revision']),
            'comments' => fake()->optional()->sentence(),
            'finalized' => false,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'finalized' => true,
        ]);
    }
}
