<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum RlhfFieldType: string
{
    use HasLabelAndValues;

    case Radio = 'radio';
    case MultiSelect = 'multi_select';
    case Text = 'text';
    case Textarea = 'textarea';
    case Dropdown = 'dropdown';

    public function label(): string
    {
        return match ($this) {
            self::Radio => 'Radio',
            self::MultiSelect => 'Multi-select',
            self::Text => 'Text',
            self::Textarea => 'Textarea',
            self::Dropdown => 'Dropdown',
        };
    }
}
