<?php

namespace Database\Factories;

use App\Models\AttemptCodingSubmission;
use App\Models\AttemptCodingTestResult;
use App\Models\CodingTestCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptCodingTestResult>
 */
class AttemptCodingTestResultFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coding_submission_id' => AttemptCodingSubmission::factory(),
            'test_case_id' => CodingTestCase::factory(),
            'passed' => fake()->boolean(70),
            'actual_output' => fake()->text(50),
            'runtime_ms' => fake()->numberBetween(10, 5000),
            'memory_kb' => fake()->numberBetween(1024, 65536),
            'error' => null,
        ];
    }

    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'passed' => true,
            'error' => null,
        ]);
    }

    public function failed(string $error = 'AssertionError'): static
    {
        return $this->state(fn (array $attributes) => [
            'passed' => false,
            'error' => $error,
        ]);
    }
}
