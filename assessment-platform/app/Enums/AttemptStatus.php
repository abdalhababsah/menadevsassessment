<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum AttemptStatus: string
{
    use HasLabelAndValues;

    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case AutoSubmitted = 'auto_submitted';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::Submitted => 'Submitted',
            self::AutoSubmitted => 'Auto-submitted',
            self::Locked => 'Locked',
        };
    }
}
