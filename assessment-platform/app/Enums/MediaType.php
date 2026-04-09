<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum MediaType: string
{
    use HasLabelAndValues;

    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
}
