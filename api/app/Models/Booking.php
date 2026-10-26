<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'customer_id',
        'service_id',
        'staff_id',
        'starts_at',
        'ends_at',
        'status',
        'source',
        'manage_token_hash',
        'note',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'        => 'datetime', // UTC
            'ends_at'          => 'datetime', // UTC
            'status'           => BookingStatus::class,
            'source'           => BookingSource::class,
            'reminder_sent_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    // ──────────────────────────────────────────────
    // Token helpers
    // ──────────────────────────────────────────────

    /**
     * Generate a new manage token, store its hash, and return the raw token.
     * The raw token is sent to the customer once and never stored.
     *
     * Token lifecycle:
     * - Created once when a booking is first made.
     * - Reusable until the booking reaches a terminal status OR starts_at has passed.
     * - Never regenerated on reschedule (same token, new time).
     */
    public static function generateManageToken(): array
    {
        $token = Str::random(64);
        $hash  = hash('sha256', $token);

        return ['token' => $token, 'hash' => $hash];
    }

    /**
     * Verify a raw manage token against the stored hash.
     * Returns false if the booking is already in a terminal state or in the past.
     */
    public function verifyManageToken(string $token): bool
    {
        if (! hash_equals($this->manage_token_hash, hash('sha256', $token))) {
            return false;
        }

        // Token is expired if the appointment has already started
        if ($this->starts_at->isPast()) {
            return false;
        }

        // Token is expired if the booking is in a terminal status
        if (in_array($this->status->value, BookingStatus::terminal(), true)) {
            return false;
        }

        return true;
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public static function generateReference(): string
    {
        do {
            $ref = strtoupper(Str::random(8));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function isInTerminalState(): bool
    {
        return in_array($this->status->value, BookingStatus::terminal(), true);
    }
}
