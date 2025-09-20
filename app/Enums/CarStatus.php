<?php

namespace App\Enums;

enum CarStatus: string
{
    case ACTIVE = 'active';
    case SOLD = 'sold';
    case HIDDEN = 'hidden';
}
