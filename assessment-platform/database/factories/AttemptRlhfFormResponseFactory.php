<?php

namespace Database\Factories;

use App\Enums\RlhfFormStage;
use App\Models\AttemptRlhfFormResponse;
use App\Models\AttemptRlhfTurn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptRlhfFormResponse>
 */
class AttemptRlhfFormResponseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rlhf_turn_id' => AttemptRlhfTurn::factory(),
            'stage' => fake()->randomElement(RlhfFormStage::cases()),
            'field_key' => fake()->slug(2),
            'value' => fake()->sentence(),
        ];
    }
}
