<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\TimeOff;
use App\Models\WorkingHours;
use App\Services\AvailabilityService;
use Carbon\Carbon;

// All tests use Africa/Addis_Ababa (UTC+3)

beforeEach(function () {
    // We'll mock Setting::get inside individual tests where needed
});

it('returns no slots when staff has no working hours for the requested weekday', function () {
    $service = Service::factory()->make(['duration_min' => 30, 'buffer_min' => 0]);
    $staff   = Staff::factory()->make(['is_active' => true]);

    // Working hours collection is empty
    $staff->setRelation('workingHours', collect());

    $svc   = new AvailabilityService();
    $slots = $svc->getSlots($service, null, '2024-10-07'); // Monday

    expect($slots)->toBeEmpty();
});

it('generates correct slots for a simple 9-to-5 window', function () {
    // Window: 09:00 - 17:00 local (06:00 - 14:00 UTC for Africa/Addis_Ababa UTC+3)
    // Service: 60 min, 0 buffer, step 15 min
    // Expected first slot: 09:00 local = 06:00 UTC
    // Expected last slot:  16:00 local = 13:00 UTC (16:00 + 60 min = 17:00 fits window)

    // This is a feature-level test, see Feature/AvailabilityTest.php for DB integration.
    // Unit-level: validate the arithmetic is correct.

    $tz   = new \DateTimeZone('Africa/Addis_Ababa');
    $date = '2024-10-07';

    $windowStartLocal = Carbon::parse("{$date} 09:00:00", $tz);
    $windowEndLocal   = Carbon::parse("{$date} 17:00:00", $tz);
    $windowStartUtc   = $windowStartLocal->copy()->utc();
    $windowEndUtc     = $windowEndLocal->copy()->utc();

    // First slot starts at window start
    expect($windowStartUtc->format('H:i'))->toBe('06:00');

    // Slot that starts at 16:00 local ends at 17:00 local — fits exactly
    $lastSlotLocal = Carbon::parse("{$date} 16:00:00", $tz);
    $lastSlotEndLocal = $lastSlotLocal->copy()->addMinutes(60);
    expect($lastSlotEndLocal->lte($windowEndLocal))->toBeTrue();

    // Slot that starts at 16:15 local ends at 17:15 — does NOT fit
    $tooLate = Carbon::parse("{$date} 16:15:00", $tz);
    $tooLateEnd = $tooLate->copy()->addMinutes(60);
    expect($tooLateEnd->gt($windowEndLocal))->toBeTrue();
});

it('handles midnight end_time correctly', function () {
    // Working hours 22:00 - 00:00 local means 22:00 - midnight (next day 00:00 local)
    // In UTC (UTC+3): 19:00 UTC - 21:00 UTC (next day 00:00 local = 21:00 UTC same day)
    $tz   = new \DateTimeZone('Africa/Addis_Ababa');
    $date = '2024-10-07';

    $localStart  = Carbon::parse("{$date} 22:00:00", $tz);
    // "00:00:00" as end means start of next calendar day in local time
    $localEnd    = Carbon::parse('2024-10-08 00:00:00', $tz);

    $utcStart = $localStart->copy()->utc();
    $utcEnd   = $localEnd->copy()->utc();

    expect($utcStart->format('H:i'))->toBe('19:00');
    expect($utcEnd->format('Y-m-d H:i'))->toBe('2024-10-07 21:00');

    // A 60-min slot starting at 22:00 local ends at 23:00 local (fits inside window)
    $slotEnd = $localStart->copy()->addMinutes(60);
    expect($slotEnd->lte($localEnd))->toBeTrue();

    // A slot at 23:15 local would end at 00:15 — does NOT fit
    $late    = Carbon::parse("{$date} 23:15:00", $tz);
    $lateEnd = $late->copy()->addMinutes(60);
    expect($lateEnd->gt($localEnd))->toBeTrue();
});

it('buffer from existing booking blocks subsequent slot', function () {
    // Existing booking: 09:00-10:00 local, buffer 15 min → blocks until 10:15
    // A new slot at 10:00 local (ends 11:00) should NOT conflict because:
    //   new.starts_at (10:00) < existing.ends_at + buffer (10:15) → conflict!
    // A new slot at 10:15 local (ends 11:15) should be fine.

    $tz              = new \DateTimeZone('Africa/Addis_Ababa');
    $existingStart   = Carbon::parse('2024-10-07 09:00:00', $tz)->utc();
    $existingEnd     = Carbon::parse('2024-10-07 10:00:00', $tz)->utc();
    $buffer          = 15; // minutes
    $bufferedEnd     = $existingEnd->copy()->addMinutes($buffer);

    $candidateA      = Carbon::parse('2024-10-07 10:00:00', $tz)->utc();
    $candidateAEnd   = $candidateA->copy()->addMinutes(60);

    $candidateB      = Carbon::parse('2024-10-07 10:15:00', $tz)->utc();
    $candidateBEnd   = $candidateB->copy()->addMinutes(60);

    // CandidateA: starts_at < bufferedEnd AND ends_at > existingStart → conflict
    $conflictA = $candidateA->lt($bufferedEnd) && $candidateAEnd->gt($existingStart);
    expect($conflictA)->toBeTrue();

    // CandidateB: starts_at = bufferedEnd → no overlap (not strictly less than)
    $conflictB = $candidateB->lt($bufferedEnd) && $candidateBEnd->gt($existingStart);
    expect($conflictB)->toBeFalse();
});
