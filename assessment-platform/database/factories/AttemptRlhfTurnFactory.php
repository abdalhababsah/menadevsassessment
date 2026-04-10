<?php

namespace Database\Factories;

use App\Enums\RlhfTurnGenerationStatus;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfTurn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptRlhfTurn>
 */
class AttemptRlhfTurnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_answer_id' => AttemptAnswer::factory(),
            'turn_number' => 1,
            'candidate_input' => fake()->paragraph(),
            'candidate_input_audio_url' => null,
            'response_a' => null,
            'response_b' => null,
            'model_a' => 'claude-sonnet-4-5-20250514',
            'model_b' => 'gpt-4o',
            'generation_status' => RlhfTurnGenerationStatus::Pending,
            'generation_error' => null,
            'generated_at' => null,
            'sxs_rating' => null,
            'sxs_justification' => null,
            'selected_side' => null,
            'selected_response_rewrite' => null,
            'rewrite_completed_at' => null,
            'completed_at' => null,
        ];
    }

    public function withResponses(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_a' => fake()->paragraphs(3, true),
            'response_b' => fake()->paragraphs(3, true),
            'generation_status' => RlhfTurnGenerationStatus::Ready,
            'generated_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->withResponses()->state(fn (array $attributes) => [
            'sxs_rating' => fake()->numberBetween(1, 5),
            'selected_side' => fake()->randomElement(['a', 'b']),
            'completed_at' => now(),
        ]);
    }
}
