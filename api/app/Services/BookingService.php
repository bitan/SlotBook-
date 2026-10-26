<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\SettingKey;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * BookingService
 *
 * Handles creating, rescheduling and cancelling bookings.
 *
 * DOUBLE-BOOKING PREVENTION (FR-05, NFR-05):
 * Availability checking + insertion is NOT safe in two separate steps because
 * two requests can both pass the check simultaneously. Instead, we:
 *   1. Open a transaction.
 *   2. Lock the staff row with SELECT ... FOR UPDATE.
 *   3. Re-check for conflicts (now holding the lock).
 *   4. Insert if clean; rollback with 409 if there's a conflict.
 *   5. Commit, then dispatch notification jobs AFTER the commit.
 *
 * This guarantees exactly one booking per slot even under concurrent requests.
 */
class BookingService
{
    // ──────────────────────────────────────────────────────────────────────────
    // Create
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create a booking.
     *
     * @param  array{
     *   service_id: int,
     *   staff_id: int,
     *   starts_at: string,    // UTC ISO-8601
     *   customer_name: string,
     *   customer_phone: string,
     *   customer_email: string|null,
     *   note: string|null,
     *   source: string,
     * } $data
     *
     * @return array{booking: Booking, manage_token: string}
     * @throws \App\Exceptions\SlotConflictException
     */
    public function create(array $data): array
    {
        $service  = Service::findOrFail($data['service_id']);
        $startsAt = Carbon::parse($data['starts_at'], 'UTC');
        $endsAt   = $startsAt->copy()->addMinutes($service->duration_min);

        $autoConfirm = (bool) Setting::get(SettingKey::AutoConfirm, true);
        $status      = $autoConfirm ? BookingStatus::Confirmed : BookingStatus::Pending;

        $result = DB::transaction(function () use ($data, $service, $startsAt, $endsAt, $status) {
            // Lock the staff row to prevent concurrent inserts for the same staff member
            Staff::lockForUpdate()->find($data['staff_id']);

            $this->assertNoConflict($data['staff_id'], $startsAt, $endsAt);

            $customer = Customer::findOrCreateByPhone(
                phone: $data['customer_phone'],
                name:  $data['customer_name'],
                email: $data['customer_email'] ?? null,
            );

            ['token' => $rawToken, 'hash' => $hash] = Booking::generateManageToken();

            $booking = Booking::create([
                'reference'         => Booking::generateReference(),
                'customer_id'       => $customer->id,
                'service_id'        => $service->id,
                'staff_id'          => $data['staff_id'],
                'starts_at'         => $startsAt,
                'ends_at'           => $endsAt,
                'status'            => $status->value,
                'source'            => $data['source'] ?? BookingSource::Online->value,
                'manage_token_hash' => $hash,
                'note'              => $data['note'] ?? null,
            ]);

            return ['booking' => $booking, 'manage_token' => $rawToken];
        });

        // Fire event AFTER commit so failed notifications never roll back the booking
        event(new BookingCreated($result['booking'], $result['manage_token']));

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Reschedule
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Reschedule an existing booking to a new time (possibly a different staff member).
     *
     * The manage token is NOT regenerated on reschedule. It remains valid until
     * the appointment starts or reaches a terminal status.
     *
     * @throws \App\Exceptions\SlotConflictException
     */
    public function reschedule(Booking $booking, int $staffId, string $startsAtUtc): Booking
    {
        $service   = $booking->service;
        $startsAt  = Carbon::parse($startsAtUtc, 'UTC');
        $endsAt    = $startsAt->copy()->addMinutes($service->duration_min);

        DB::transaction(function () use ($booking, $staffId, $startsAt, $endsAt) {
            Staff::lockForUpdate()->find($staffId);

            // Exclude the current booking from the conflict check (it's being moved)
            $this->assertNoConflict($staffId, $startsAt, $endsAt, excludeBookingId: $booking->id);

            $booking->update([
                'staff_id'  => $staffId,
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
                'status'    => BookingStatus::Confirmed->value,
            ]);
        });

        return $booking->fresh();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cancel
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(Booking $booking): Booking
    {
        $booking->update(['status' => BookingStatus::Cancelled->value]);

        return $booking->fresh();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Conflict detection
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Check whether a new or rescheduled booking would overlap an existing one.
     *
     * An overlap occurs when:
     *   new.starts_at < existing.ends_at + buffer  AND  new.ends_at > existing.starts_at
     *
     * We include the buffer of the *existing* booking in the comparison because
     * the buffer time is reserved for the staff member (cleaning, rest, etc.).
     *
     * @throws \App\Exceptions\SlotConflictException
     */
    private function assertNoConflict(
        int    $staffId,
        Carbon $startsAt,
        Carbon $endsAt,
        ?int   $excludeBookingId = null,
    ): void {
        // We need to account for buffers on existing bookings.
        // We join services to add buffer_min to ends_at dynamically.
        $query = Booking::query()
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->where('bookings.staff_id', $staffId)
            ->whereIn('bookings.status', BookingStatus::blocking())
            ->where('bookings.starts_at', '<', $endsAt)
            ->whereRaw('DATE_ADD(bookings.ends_at, INTERVAL services.buffer_min MINUTE) > ?', [$startsAt]);

        if ($excludeBookingId !== null) {
            $query->where('bookings.id', '!=', $excludeBookingId);
        }

        if ($query->exists()) {
            throw new \App\Exceptions\SlotConflictException();
        }
    }
}
