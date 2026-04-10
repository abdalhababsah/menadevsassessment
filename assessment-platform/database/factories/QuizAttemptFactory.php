<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Enums\RlhfReviewStatus;
use App\Models\Candidate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory()->published(),
            'candidate_id' => Candidate::factory(),
            'invitation_id' => null,
            'started_at' => now(),
            'submitted_at' => null,
            'status' => AttemptStatus::InProgress,
            'auto_score' => null,
            'final_score' => null,
            'rlhf_review_status' => RlhfReviewStatus::NotRequired,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function autoSubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttemptStatus::AutoSubmitted,
            'submitted_at' => now(),
        ]);
    }

    public function scored(float $score = 85.00): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'auto_score' => $score,
            'final_score' => $score,
        ]);
    }
}
