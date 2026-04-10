<?php

namespace Database\Factories;

use App\Models\AttemptRlhfEvaluation;
use App\Models\AttemptRlhfTurn;
use App\Models\RlhfCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptRlhfEvaluation>
 */
class AttemptRlhfEvaluationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rlhf_turn_id' => AttemptRlhfTurn::factory(),
            'criterion_id' => RlhfCriterion::factory(),
            'response_side' => fake()->randomElement(['a', 'b']),
            'rating_value' => (string) fake()->numberBetween(1, 5),
            'justification' => fake()->optional()->sentence(),
        ];
    }
}
