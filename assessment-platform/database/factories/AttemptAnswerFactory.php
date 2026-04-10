<?php

namespace Database\Factories;

use App\Enums\AnswerStatus;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptAnswer>
 */
class AttemptAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'question_id' => Question::factory(),
            'question_version' => 1,
            'answered_at' => null,
            'time_spent_seconds' => 0,
            'auto_score' => null,
            'reviewer_score' => null,
            'status' => AnswerStatus::Unanswered,
        ];
    }

    public function answered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnswerStatus::Answered,
            'answered_at' => now(),
            'time_spent_seconds' => fake()->numberBetween(10, 300),
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnswerStatus::Skipped,
        ]);
    }
}
