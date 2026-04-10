<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\RlhfQuestionConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RlhfQuestionConfig>
 */
class RlhfQuestionConfigFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->rlhf(),
            'number_of_turns' => fake()->numberBetween(1, 5),
            'candidate_input_mode' => fake()->randomElement(['text', 'voice', 'both']),
            'model_a' => 'claude-sonnet-4-5-20250514',
            'model_b' => 'gpt-4o',
            'generation_params' => ['temperature' => 0.7, 'max_tokens' => 2048],
            'enable_pre_prompt_form' => false,
            'enable_post_prompt_form' => true,
            'enable_rewrite_step' => false,
            'enable_post_rewrite_form' => false,
            'guidelines_markdown' => fake()->optional()->paragraphs(2, true),
        ];
    }

    public function withAllForms(): static
    {
        return $this->state(fn (array $attributes) => [
            'enable_pre_prompt_form' => true,
            'enable_post_prompt_form' => true,
            'enable_rewrite_step' => true,
            'enable_post_rewrite_form' => true,
        ]);
    }

    public function multiTurn(int $turns = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'number_of_turns' => $turns,
        ]);
    }
}
