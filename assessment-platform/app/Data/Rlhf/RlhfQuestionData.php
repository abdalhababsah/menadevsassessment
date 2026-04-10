<?php

namespace App\Data\Rlhf;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class RlhfQuestionData extends Data
{
    /**
     * @param  array<int>  $tags
     * @param  array<string, mixed>|null  $generation_params
     * @param  array<int, RlhfCriterionData>  $criteria
     * @param  array<int, RlhfFormFieldData>  $form_fields
     */
    public function __construct(
        public string $stem,
        public ?string $instructions,
        public string $difficulty,
        public float $points,
        public ?int $time_limit_seconds,
        public array $tags,
        public int $number_of_turns,
        public string $candidate_input_mode,
        public string $model_a,
        public string $model_b,
        public ?array $generation_params,
        public bool $enable_pre_prompt_form,
        public bool $enable_post_prompt_form,
        public bool $enable_rewrite_step,
        public bool $enable_post_rewrite_form,
        public ?string $guidelines_markdown,
        #[DataCollectionOf(RlhfCriterionData::class)]
        public array $criteria,
        #[DataCollectionOf(RlhfFormFieldData::class)]
        public array $form_fields,
    ) {}
}
