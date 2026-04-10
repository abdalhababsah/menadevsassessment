<?php

namespace Database\Factories;

use App\Enums\RlhfFieldType;
use App\Enums\RlhfFormStage;
use App\Models\Question;
use App\Models\RlhfQuestionFormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RlhfQuestionFormField>
 */
class RlhfQuestionFormFieldFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->rlhf(),
            'stage' => fake()->randomElement(RlhfFormStage::cases()),
            'field_key' => fake()->unique()->slug(2),
            'label' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'field_type' => fake()->randomElement(RlhfFieldType::cases()),
            'options' => null,
            'required' => true,
            'min_length' => null,
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    public function prePrompt(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => RlhfFormStage::PrePrompt,
        ]);
    }

    public function postPrompt(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => RlhfFormStage::PostPrompt,
        ]);
    }

    public function postRewrite(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => RlhfFormStage::PostRewrite,
        ]);
    }

    public function radio(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => RlhfFieldType::Radio,
            'options' => ['Option A', 'Option B', 'Option C'],
        ]);
    }

    public function textarea(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => RlhfFieldType::Textarea,
            'min_length' => 50,
        ]);
    }
}
