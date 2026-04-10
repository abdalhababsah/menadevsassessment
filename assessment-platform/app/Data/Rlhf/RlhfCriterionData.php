<?php

namespace App\Data\Rlhf;

use Spatie\LaravelData\Data;

final class RlhfCriterionData extends Data
{
    /**
     * @param  array<int|string, string>  $scale_labels
     * @param  array<int, int|string>  $justification_required_when
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $scale_type,
        public array $scale_labels,
        public array $justification_required_when,
        public int $position,
    ) {}
}
