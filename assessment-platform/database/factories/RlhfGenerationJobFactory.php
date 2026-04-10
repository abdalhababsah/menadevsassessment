<?php

namespace Database\Factories;

use App\Enums\RlhfTurnGenerationStatus;
use App\Models\AttemptRlhfTurn;
use App\Models\RlhfGenerationJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RlhfGenerationJob>
 */
class RlhfGenerationJobFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rlhf_turn_id' => AttemptRlhfTurn::factory(),
            'side' => fake()->randomElement(['a', 'b']),
            'status' => RlhfTurnGenerationStatus::Pending,
            'attempts' => 0,
            'last_error' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
