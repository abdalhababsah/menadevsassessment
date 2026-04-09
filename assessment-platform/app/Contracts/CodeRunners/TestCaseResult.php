<?php

namespace App\Contracts\CodeRunners;

use Spatie\LaravelData\Data;

final class TestCaseResult extends Data
{
    public function __construct(
        public string $name,
        public bool $passed,
        public ?string $output = null,
        public ?string $error = null,
        public ?float $executionTimeMs = null,
    ) {}
}
