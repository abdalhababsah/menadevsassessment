<?php

namespace Database\Factories;

use App\Enums\QuizNavigationMode;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'time_limit_seconds' => fake()->optional()->numberBetween(1800, 7200),
            'passing_score' => fake()->optional()->randomFloat(2, 50, 90),
            'randomize_questions' => false,
            'randomize_options' => false,
            'navigation_mode' => QuizNavigationMode::Free,
            'camera_enabled' => false,
            'anti_cheat_enabled' => false,
            'max_fullscreen_exits' => 3,
            'starts_at' => null,
            'ends_at' => null,
            'status' => QuizStatus::Draft,
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuizStatus::Published,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuizStatus::Archived,
        ]);
    }

    public function withTimeWindow(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuizStatus::Published,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function forwardOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'navigation_mode' => QuizNavigationMode::ForwardOnly,
        ]);
    }

    public function withAntiCheat(): static
    {
        return $this->state(fn (array $attributes) => [
            'camera_enabled' => true,
            'anti_cheat_enabled' => true,
        ]);
    }
}
