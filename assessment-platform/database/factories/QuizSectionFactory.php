<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizSection>
 */
class QuizSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'time_limit_seconds' => null,
            'position' => 0,
        ];
    }
}
