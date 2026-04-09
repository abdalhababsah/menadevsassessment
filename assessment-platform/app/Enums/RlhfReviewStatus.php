<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum RlhfReviewStatus: string
{
    use HasLabelAndValues;

    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Not required',
            self::Pending => 'Pending',
            self::Completed => 'Completed',
        };
    }
}
