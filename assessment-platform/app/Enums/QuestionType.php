<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum QuestionType: string
{
    use HasLabelAndValues;

    case SingleSelect = 'single_select';
    case MultiSelect = 'multi_select';
    case Coding = 'coding';
    case Rlhf = 'rlhf';
}
