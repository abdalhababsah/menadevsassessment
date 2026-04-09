<?php

namespace App\Enums\Concerns;

trait HasLabelAndValues
{
    public function label(): string
    {
        return str_replace('_', ' ', ucfirst($this->value));
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
