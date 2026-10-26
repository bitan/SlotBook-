<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow    = 'no_show';

    /** Statuses that block a time slot for availability calculations. */
    public static function blocking(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }

    /** Statuses that represent a finished booking (no future changes expected). */
    public static function terminal(): array
    {
        return [self::Completed->value, self::Cancelled->value, self::NoShow->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow    => 'No Show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'yellow',
            self::Confirmed => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'gray',
            self::NoShow    => 'red',
        };
    }
}
