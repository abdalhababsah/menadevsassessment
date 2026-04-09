<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum RlhfTurnGenerationStatus: string
{
    use HasLabelAndValues;

    case Pending = 'pending';
    case Generating = 'generating';
    case Ready = 'ready';
    case Failed = 'failed';
}
