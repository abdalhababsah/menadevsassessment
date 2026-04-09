<?php

namespace App\Contracts\CodeRunners;

use Spatie\LaravelData\Data;

final class TestRunResult extends Data
{
    /**
     * @param  array<int, TestCaseResult>  $results
     */
    public function __construct(
        public bool $compiled,
        public int $passed,
        public int $failed,
        public int $total,
        /** @var array<int, TestCaseResult> */
        public array $results,
        public ?string $compileError = null,
        public ?float $totalExecutionTimeMs = null,
    ) {}
}
