<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum QuizStatus: string
{
    use HasLabelAndValues;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
