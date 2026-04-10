<?php

namespace App\Data\Questions;

use Spatie\LaravelData\Data;

final class QuestionOptionData extends Data
{
    public function __construct(
        public string $content,
        public bool $is_correct,
        public int $position,
        public string $content_type = 'text',
    ) {}
}
