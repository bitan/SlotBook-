<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SettingKey;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\TimeOff;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Collection;

/**
 * AvailabilityService
 *
 * Calculates free booking slots for a given service, staff member, and date.
 *
 * Key design decisions:
 *
 * 1. TIMEZONE HANDLING
 *    All working_hours rows store LOCAL times (Africa/Addis_Ababa).
 *    We convert to UTC per calendar date using:
 *        Carbon::parse("{$date} {$localTime}", $tz)->utc()
 *    This is safe across DST changes and avoids the midnight edge case because
 *    the UTC offset is computed for the specific date being queried, not
 *    generically. A local time of "23:00" on one date converts correctly
 *    to UTC regardless of what UTC offset that date happens to have.
 *
 * 2. MIDNIGHT END-TIME CONVENTION
 *    MySQL TIME cannot store "24:00:00", so end_time = "00:00:00" means
 *    midnight (end of that local day). We detect this with
 *    WorkingHours::endsAtMidnight() and add 1 day to the Carbon instance.
 *    This handles businesses that work until midnight.
 *
 * 3. SLOT STEP
 *    Slots are generated in 15-minute increments. A slot is valid if
 *    start + duration + buffer fits entirely within a free window.
 *
 * 4. "ANY AVAILABLE" LOGIC
 *    When staff_id is null, we compute slots for every eligible active
 *    staff member and merge them. Each returned slot carries the staff_id
 *    that will be assigned (the first available one for that time).
 *
 * 5. MINIMUM NOTICE
 *    Slots whose start time is less than `min_notice_minutes` from now are hidden.
 */
class AvailabilityService
{
    private const SLOT_STEP_MINUTES = 15;

    private string $timezone;
    private int    $minNoticeMinutes;

    public function __construct()
    {
        $this->timezone         = Setting::get(SettingKey::BusinessTimezone, 'Africa/Addis_Ababa');
        $this->minNoticeMinutes = (int) Setting::get(SettingKey::MinNoticeMinutes, 60);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get available slots for a service on a date.
     *
     * @param  Service     $service
     * @param  string|null $staffId  null means "any available"
     * @param  string      $date     Local date string (Y-m-d) in business timezone
     * @return Collection  Each item: ['starts_at' => Carbon (UTC), 'staff_id' => int]
     */
    public function getSlots(Service $service, ?int $staffId, string $date): Collection
    {
        $localDate = CarbonImmutable::createFromFormat('Y-m-d', $date, $this->timezone)
            ->startOfDay();

        // Earliest allowed start (now + min notice), in UTC
        $earliest = CarbonImmutable::now('UTC')->addMinutes($this->minNoticeMinutes);

        if ($staffId !== null) {
            $staff = Staff::find($staffId);
            if (! $staff || ! $staff->is_active) {
                return collect();
            }

            return $this->getSlotsForStaff($service, $staff, $localDate, $earliest);
        }

        // "Any available" — merge across all eligible active staff
        $eligibleStaff = Staff::query()
            ->whereHas('services', fn ($q) => $q->where('services.id', $service->id))
            ->where('is_active', true)
            ->get();

        // For each time window, assign the first available staff member
        $slotMap = collect(); // keyed by starts_at ISO string

        foreach ($eligibleStaff as $member) {
            $memberSlots = $this->getSlotsForStaff($service, $member, $localDate, $earliest);

            foreach ($memberSlots as $slot) {
                $key = $slot['starts_at']->toISOString();
                if (! $slotMap->has($key)) {
                    $slotMap->put($key, $slot);
                }
            }
        }

        return $slotMap->values()->sortBy(fn ($s) => $s['starts_at']->timestamp)->values();
    }

    /**
     * Return available dates (Y-m-d strings in local timezone) for the next 30 days.
     */
    public function getAvailableDates(Service $service, ?int $staffId): Collection
    {
        $dates = collect();
        $today = CarbonImmutable::today($this->timezone);

        for ($i = 0; $i < 30; $i++) {
            $date    = $today->addDays($i)->format('Y-m-d');
            $slots   = $this->getSlots($service, $staffId, $date);
            if ($slots->isNotEmpty()) {
                $dates->push($date);
            }
        }

        return $dates;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Compute free slots for a single staff member on a single date.
     */
    private function getSlotsForStaff(
        Service         $service,
        Staff           $staff,
        CarbonImmutable $localDate,
        CarbonImmutable $earliest,
    ): Collection {
        $slots = collect();

        // 1. Resolve working windows for this weekday (in UTC)
        $workingWindows = $this->getWorkingWindowsUtc($staff, $localDate);
        if ($workingWindows->isEmpty()) {
            return $slots;
        }

        // 2. Resolve busy intervals (existing bookings + time off) in UTC
        $busyIntervals = $this->getBusyIntervals($staff, $localDate, $service);

        // 3. For each working window, step through and find valid slots
        $totalBlockMinutes = $service->totalBlockMinutes(); // duration + buffer

        foreach ($workingWindows as [$windowStart, $windowEnd]) {
            $cursor = $windowStart->copy();

            while (true) {
                $slotEnd     = $cursor->copy()->addMinutes($service->duration_min);
                $blockEnd    = $cursor->copy()->addMinutes($totalBlockMinutes);

                // Slot + buffer must fit within the working window
                if ($blockEnd->gt($windowEnd)) {
                    break;
                }

                // Skip slots before the minimum notice threshold
                if ($cursor->lt($earliest)) {
                    $cursor = $cursor->addMinutes(self::SLOT_STEP_MINUTES);
                    continue;
                }

                // Check if this slot (including buffer) overlaps any busy interval
                if (! $this->overlapsAny($cursor, $blockEnd, $busyIntervals)) {
                    $slots->push([
                        'starts_at' => $cursor->copy(),
                        'staff_id'  => $staff->id,
                    ]);
                }

                $cursor = $cursor->addMinutes(self::SLOT_STEP_MINUTES);
            }
        }

        return $slots;
    }

    /**
     * Convert working_hours rows for this staff + weekday into UTC [start, end] pairs.
     *
     * MIDNIGHT EDGE CASE:
     * MySQL TIME max value is '23:59:59'. We store midnight end as '00:00:00'.
     * WorkingHours::endsAtMidnight() detects this, and we set the UTC end to
     * the start of the NEXT UTC day at the same moment as local midnight.
     *
     * @return Collection  Each item: [CarbonImmutable $start, CarbonImmutable $end] (UTC)
     */
    private function getWorkingWindowsUtc(Staff $staff, CarbonImmutable $localDate): Collection
    {
        $weekday = $localDate->dayOfWeek; // 0 = Sunday

        return $staff->workingHours
            ->filter(fn ($wh) => $wh->weekday === $weekday)
            ->map(function ($wh) use ($localDate) {
                $tz    = new CarbonTimeZone($this->timezone);

                // Build local Carbon datetimes for this calendar date
                $localStart = Carbon::parse(
                    $localDate->format('Y-m-d') . ' ' . $wh->start_time,
                    $tz,
                )->utc();

                if ($wh->endsAtMidnight()) {
                    // '00:00:00' means end of this local day = next calendar date at 00:00 local
                    $localEnd = Carbon::parse(
                        $localDate->addDay()->format('Y-m-d') . ' 00:00:00',
                        $tz,
                    )->utc();
                } else {
                    $localEnd = Carbon::parse(
                        $localDate->format('Y-m-d') . ' ' . $wh->end_time,
                        $tz,
                    )->utc();
                }

                return [
                    CarbonImmutable::instance($localStart),
                    CarbonImmutable::instance($localEnd),
                ];
            })
            ->values();
    }

    /**
     * Collect all busy intervals for this staff member on this date.
     * Busy = existing pending/confirmed bookings (including their buffer)
     *        + staff's own time off
     *        + business-wide time off (staff_id IS NULL)
     *
     * @return Collection  Each item: [Carbon $start, Carbon $end] (UTC)
     */
    private function getBusyIntervals(
        Staff           $staff,
        CarbonImmutable $localDate,
        Service         $service,
    ): Collection {
        $tz = new CarbonTimeZone($this->timezone);

        // Day boundaries in UTC (to narrow the query range)
        $dayStart = Carbon::parse($localDate->format('Y-m-d') . ' 00:00:00', $tz)->utc();
        $dayEnd   = Carbon::parse($localDate->format('Y-m-d') . ' 00:00:00', $tz)
            ->addDay()->utc();

        // Existing bookings (blocking statuses only; buffer included in ends_at for overlap)
        $bookings = Booking::query()
            ->where('staff_id', $staff->id)
            ->whereIn('status', BookingStatus::blocking())
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get(['starts_at', 'ends_at', 'service_id']);

        $intervals = collect();

        foreach ($bookings as $booking) {
            // Extend the booked ends_at by the service's buffer for that booking
            $bookedService = Service::find($booking->service_id);
            $bufferedEnd   = $booking->ends_at->copy()->addMinutes($bookedService?->buffer_min ?? 0);
            $intervals->push([
                CarbonImmutable::instance($booking->starts_at),
                CarbonImmutable::instance($bufferedEnd),
            ]);
        }

        // Time off: staff-specific and business-wide
        $timeOffs = TimeOff::query()
            ->where(fn ($q) => $q->where('staff_id', $staff->id)->orWhereNull('staff_id'))
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get(['starts_at', 'ends_at']);

        foreach ($timeOffs as $to) {
            $intervals->push([
                CarbonImmutable::instance($to->starts_at),
                CarbonImmutable::instance($to->ends_at),
            ]);
        }

        return $intervals;
    }

    /**
     * Returns true if the interval [$start, $end) overlaps any interval in $busyList.
     */
    private function overlapsAny(
        CarbonImmutable $start,
        CarbonImmutable $end,
        Collection      $busyList,
    ): bool {
        foreach ($busyList as [$busyStart, $busyEnd]) {
            // Standard overlap: start < busyEnd AND end > busyStart
            if ($start->lt($busyEnd) && $end->gt($busyStart)) {
                return true;
            }
        }

        return false;
    }
}
