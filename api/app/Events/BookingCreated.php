<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
        /** Raw manage token (not the hash). Passed to the notification job to include in emails. */
        public readonly string $manageToken,
    ) {}
}
