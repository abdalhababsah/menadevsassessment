<?php

namespace Database\Factories;

use App\Models\AttemptAnswer;
use App\Models\AttemptAnswerSelection;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptAnswerSelection>
 */
class AttemptAnswerSelectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_answer_id' => AttemptAnswer::factory(),
            'question_option_id' => QuestionOption::factory(),
        ];
    }
}
