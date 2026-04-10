<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum AnswerStatus: string
{
    use HasLabelAndValues;

    case Unanswered = 'unanswered';
    case Answered = 'answered';
    case Skipped = 'skipped';
    case Flagged = 'flagged';
}
