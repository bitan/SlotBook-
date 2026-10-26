<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Jobs\SendBookingConfirmationEmail;
use App\Jobs\SendTelegramAlert;

class DispatchBookingNotifications
{
    public function handle(BookingCreated $event): void
    {
        SendBookingConfirmationEmail::dispatch($event->booking, $event->manageToken);
        SendTelegramAlert::dispatch($event->booking);
    }
}
