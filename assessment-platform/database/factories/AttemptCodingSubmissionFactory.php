<?php

namespace Database\Factories;

use App\Models\AttemptAnswer;
use App\Models\AttemptCodingSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptCodingSubmission>
 */
class AttemptCodingSubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_answer_id' => AttemptAnswer::factory(),
            'language' => fake()->randomElement(['python', 'javascript', 'java']),
            'code' => 'def solution():\n    return 42',
            'submitted_at' => now(),
        ];
    }
}
