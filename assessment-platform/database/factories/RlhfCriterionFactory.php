<?php

namespace Database\Factories;

use App\Enums\RlhfScaleType;
use App\Models\Question;
use App\Models\RlhfCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RlhfCriterion>
 */
class RlhfCriterionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->rlhf(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'scale_type' => fake()->randomElement(RlhfScaleType::cases()),
            'scale_labels' => ['1' => 'Poor', '2' => 'Fair', '3' => 'Good'],
            'justification_required_when' => [1, 3],
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    public function threePointQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'scale_type' => RlhfScaleType::ThreePointQuality,
            'scale_labels' => ['1' => 'Poor', '2' => 'Acceptable', '3' => 'Excellent'],
        ]);
    }

    public function fivePointCentered(): static
    {
        return $this->state(fn (array $attributes) => [
            'scale_type' => RlhfScaleType::FivePointCentered,
            'scale_labels' => [
                '1' => 'Much worse',
                '2' => 'Slightly worse',
                '3' => 'About the same',
                '4' => 'Slightly better',
                '5' => 'Much better',
            ],
        ]);
    }
}
