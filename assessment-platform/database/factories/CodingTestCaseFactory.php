<?php

namespace Database\Factories;

use App\Models\CodingTestCase;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodingTestCase>
 */
class CodingTestCaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->coding(),
            'input' => fake()->text(100),
            'expected_output' => fake()->text(100),
            'is_hidden' => true,
            'weight' => 1.00,
        ];
    }

    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => false,
        ]);
    }
}
