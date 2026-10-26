<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingSource: string
{
    case Online = 'online';
    case Manual = 'manual';
}
