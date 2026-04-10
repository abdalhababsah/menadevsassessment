<?php

namespace App\Data\Questions;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class CodingQuestionData extends Data
{
    /**
     * @param  array<int>  $tags
     * @param  array<int, string>  $allowed_languages
     * @param  array<string, string>|null  $starter_code
     * @param  array<int, CodingTestCaseData>  $test_cases
     */
    public function __construct(
        public string $stem,
        public ?string $instructions,
        public string $difficulty,
        public float $points,
        public ?int $time_limit_seconds,
        public array $tags,
        public array $allowed_languages,
        public ?array $starter_code,
        public int $time_limit_ms,
        public int $memory_limit_mb,
        #[DataCollectionOf(CodingTestCaseData::class)]
        public array $test_cases,
    ) {}
}
