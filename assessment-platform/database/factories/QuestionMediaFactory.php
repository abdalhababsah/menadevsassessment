<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionMedia>
 */
class QuestionMediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'media_type' => fake()->randomElement(MediaType::cases()),
            'url' => fake()->imageUrl(),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
