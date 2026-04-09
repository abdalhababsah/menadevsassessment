<?php

namespace App\Contracts\AiProviders;

use Spatie\LaravelData\Data;

final class GeneratedResponse extends Data
{
    public function __construct(
        public string $content,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public ?string $stopReason = null,
    ) {}
}
