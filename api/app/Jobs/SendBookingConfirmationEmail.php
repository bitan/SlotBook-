<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60; // seconds

    public function __construct(
        private readonly Booking $booking,
        private readonly string  $manageToken,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->sendCustomerConfirmation($this->booking, $this->manageToken);
    }
}
