<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum SelectedSide: string
{
    use HasLabelAndValues;

    case A = 'a';
    case B = 'b';
}
