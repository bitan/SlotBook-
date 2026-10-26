<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Enums\SettingKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * NotificationService
 *
 * Sends emails to customers and Telegram messages to the owner.
 * Called from queued jobs so failures never block or roll back a booking.
 * Every attempt is written to notification_logs.
 */
class NotificationService
{
    public function sendCustomerConfirmation(Booking $booking, string $manageToken): void
    {
        $customer = $booking->customer;
        if (! $customer->email) {
            return; // No email address — nothing to send
        }

        try {
            Mail::send('emails.booking-confirmation', [
                'booking'     => $booking,
                'manageToken' => $manageToken,
                'manageUrl'   => config('app.frontend_url') . '/booking?t=' . $manageToken,
            ], function ($m) use ($customer, $booking) {
                $m->to($customer->email, $customer->name)
                  ->subject('Your booking is confirmed — Ref: ' . $booking->reference);
            });

            $this->log($booking, NotificationChannel::Email, 'sent');
        } catch (\Throwable $e) {
            Log::error('CustomerConfirmationEmail failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
            $this->log($booking, NotificationChannel::Email, 'failed', $e->getMessage());
        }
    }

    public function sendOwnerAlert(Booking $booking): void
    {
        $chatId = Setting::get(SettingKey::TelegramOwnerChatId);
        $token  = config('services.telegram.bot_token');

        if ($chatId && $token) {
            $this->sendTelegram($booking, $chatId, $token);
        } else {
            // Fallback to email if Telegram is not configured
            $ownerEmail = Setting::get(SettingKey::BusinessEmail);
            if ($ownerEmail) {
                $this->sendOwnerEmail($booking, $ownerEmail);
            }
        }
    }

    public function sendReminder(Booking $booking): void
    {
        $customer = $booking->customer;
        if (! $customer->email) {
            return;
        }

        try {
            Mail::send('emails.booking-reminder', [
                'booking' => $booking,
            ], function ($m) use ($customer, $booking) {
                $m->to($customer->email, $customer->name)
                  ->subject('Reminder: Your appointment tomorrow — Ref: ' . $booking->reference);
            });

            $this->log($booking, NotificationChannel::Email, 'sent');
            $booking->update(['reminder_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('ReminderEmail failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
            $this->log($booking, NotificationChannel::Email, 'failed', $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function sendTelegram(Booking $booking, string $chatId, string $token): void
    {
        $service  = $booking->service;
        $customer = $booking->customer;
        $staff    = $booking->staff;
        $tz       = Setting::get(SettingKey::BusinessTimezone, 'Africa/Addis_Ababa');

        $time = $booking->starts_at->copy()->timezone($tz)->format('D, M j \a\t H:i');
        $text = "📅 *New Booking*\n"
            . "Ref: `{$booking->reference}`\n"
            . "Service: {$service->name}\n"
            . "Staff: {$staff->display_name}\n"
            . "Customer: {$customer->name} ({$customer->phone})\n"
            . "Time: {$time}";

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                $this->log($booking, NotificationChannel::Telegram, 'sent');
            } else {
                throw new \RuntimeException($response->body());
            }
        } catch (\Throwable $e) {
            Log::error('TelegramAlert failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
            $this->log($booking, NotificationChannel::Telegram, 'failed', $e->getMessage());
        }
    }

    private function sendOwnerEmail(Booking $booking, string $ownerEmail): void
    {
        try {
            Mail::send('emails.owner-alert', ['booking' => $booking], function ($m) use ($ownerEmail, $booking) {
                $m->to($ownerEmail)
                  ->subject('New booking: ' . $booking->reference);
            });
            $this->log($booking, NotificationChannel::Email, 'sent');
        } catch (\Throwable $e) {
            $this->log($booking, NotificationChannel::Email, 'failed', $e->getMessage());
        }
    }

    private function log(
        Booking             $booking,
        NotificationChannel $channel,
        string              $status,
        ?string             $error = null,
    ): void {
        NotificationLog::create([
            'booking_id' => $booking->id,
            'channel'    => $channel->value,
            'status'     => $status,
            'error'      => $error,
        ]);
    }
}
