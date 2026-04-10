<?php

namespace App\Data\Questions;

use Spatie\LaravelData\Data;

final class CodingTestCaseData extends Data
{
    public function __construct(
        public string $input,
        public string $expected_output,
        public bool $is_hidden,
        public float $weight,
    ) {}
}
