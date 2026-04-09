<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum QuizNavigationMode: string
{
    use HasLabelAndValues;

    case ForwardOnly = 'forward_only';
    case Free = 'free';

    public function label(): string
    {
        return match ($this) {
            self::ForwardOnly => 'Forward only',
            self::Free => 'Free',
        };
    }
}
