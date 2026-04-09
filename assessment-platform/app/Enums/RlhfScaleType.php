<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum RlhfScaleType: string
{
    use HasLabelAndValues;

    case ThreePointQuality = 'three_point_quality';
    case FivePointCentered = 'five_point_centered';
    case FivePointSatisfaction = 'five_point_satisfaction';

    public function label(): string
    {
        return match ($this) {
            self::ThreePointQuality => '3-point quality',
            self::FivePointCentered => '5-point centered',
            self::FivePointSatisfaction => '5-point satisfaction',
        };
    }
}
