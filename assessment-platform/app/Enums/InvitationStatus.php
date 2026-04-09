<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum InvitationStatus: string
{
    use HasLabelAndValues;

    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Exhausted = 'exhausted';
}
