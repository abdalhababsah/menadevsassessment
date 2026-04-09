<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum RlhfFormStage: string
{
    use HasLabelAndValues;

    case PrePrompt = 'pre_prompt';
    case PostPrompt = 'post_prompt';
    case PostRewrite = 'post_rewrite';

    public function label(): string
    {
        return match ($this) {
            self::PrePrompt => 'Pre-prompt',
            self::PostPrompt => 'Post-prompt',
            self::PostRewrite => 'Post-rewrite',
        };
    }
}
