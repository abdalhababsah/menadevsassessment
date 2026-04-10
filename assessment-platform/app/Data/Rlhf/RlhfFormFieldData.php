<?php

namespace App\Data\Rlhf;

use Spatie\LaravelData\Data;

final class RlhfFormFieldData extends Data
{
    /**
     * @param  array<int, string>|null  $options
     */
    public function __construct(
        public string $stage,
        public string $field_key,
        public string $label,
        public ?string $description,
        public string $field_type,
        public ?array $options,
        public bool $required,
        public ?int $min_length,
        public int $position,
    ) {}
}
