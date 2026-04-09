<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum QuestionDifficulty: string
{
    use HasLabelAndValues;

    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';
}
