<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Manual = 'manual';
    case Order = 'order';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Order => 'Compra',
        };
    }
}
