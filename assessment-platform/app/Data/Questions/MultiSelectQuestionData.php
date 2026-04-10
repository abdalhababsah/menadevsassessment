<?php

namespace App\Data\Questions;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class MultiSelectQuestionData extends Data
{
    /**
     * @param  array<int>  $tags
     * @param  array<int, QuestionOptionData>  $options
     */
    public function __construct(
        public string $stem,
        public ?string $instructions,
        public string $difficulty,
        public float $points,
        public ?int $time_limit_seconds,
        public array $tags,
        #[DataCollectionOf(QuestionOptionData::class)]
        public array $options,
    ) {}
}
