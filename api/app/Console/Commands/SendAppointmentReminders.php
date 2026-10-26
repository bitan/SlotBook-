<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Jobs\SendReminderEmail;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Scheduled every 15 minutes.
 * Finds confirmed bookings starting in ~24 hours with no reminder sent yet,
 * and dispatches the reminder job.
 */
class SendAppointmentReminders extends Command
{
    protected $signature   = 'bookings:send-reminders';
    protected $description = 'Send 24-hour reminder emails for upcoming confirmed bookings.';

    public function handle(): int
    {
        $from = Carbon::now()->addHours(23)->addMinutes(45);
        $to   = Carbon::now()->addHours(24)->addMinutes(15);

        $bookings = Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [$from, $to])
            ->with(['customer', 'service', 'staff'])
            ->get();

        foreach ($bookings as $booking) {
            SendReminderEmail::dispatch($booking);
        }

        $this->info("Dispatched {$bookings->count()} reminder(s).");

        return self::SUCCESS;
    }
}
