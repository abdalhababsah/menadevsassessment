<?php

namespace Database\Factories;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(QuestionType::cases()),
            'stem' => fake()->paragraph(),
            'instructions' => fake()->optional()->sentence(),
            'difficulty' => fake()->randomElement(QuestionDifficulty::cases()),
            'points' => fake()->randomFloat(2, 1, 10),
            'time_limit_seconds' => fake()->optional()->numberBetween(30, 600),
            'version' => 1,
            'parent_question_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function singleSelect(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::SingleSelect,
        ]);
    }

    public function multiSelect(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::MultiSelect,
        ]);
    }

    public function coding(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Coding,
        ]);
    }

    public function rlhf(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Rlhf,
        ]);
    }

    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => QuestionDifficulty::Easy,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => QuestionDifficulty::Medium,
        ]);
    }

    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => QuestionDifficulty::Hard,
        ]);
    }
}
