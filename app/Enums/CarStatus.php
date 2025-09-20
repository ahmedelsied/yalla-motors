<?php

namespace App\Enums;

enum CarStatus: string
{
    case ACTIVE = 'active';
    case SOLD = 'sold';
    case HIDDEN = 'hidden';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
