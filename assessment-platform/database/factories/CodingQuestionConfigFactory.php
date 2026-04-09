<?php

namespace Database\Factories;

use App\Models\CodingQuestionConfig;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodingQuestionConfig>
 */
class CodingQuestionConfigFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->coding(),
            'allowed_languages' => ['python', 'javascript'],
            'starter_code' => [
                'python' => '# Write your solution here',
                'javascript' => '// Write your solution here',
            ],
            'time_limit_ms' => 10000,
            'memory_limit_mb' => 256,
        ];
    }
}
