<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\BookingStatus;
use App\Enums\SettingKey;
use App\Exceptions\SlotConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CreateBookingRequest;
use App\Http\Requests\Public\RescheduleBookingRequest;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    /** POST /bookings — create a new booking (public, throttled) */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        try {
            ['booking' => $booking] = $this->bookingService->create($request->validated());

            return response()->json([
                'data' => [
                    'reference'  => $booking->reference,
                    'status'     => $booking->status->value,
                    'starts_at'  => $booking->starts_at->toISOString(),
                    'ends_at'    => $booking->ends_at->toISOString(),
                ],
            ], 201);
        } catch (SlotConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code'    => 'SLOT_CONFLICT',
            ], 409);
        }
    }

    /** GET /bookings/manage/{token} — customer views their booking */
    public function show(string $token): JsonResponse
    {
        $booking = $this->resolveByToken($token);
        if (! $booking) {
            return $this->tokenError();
        }

        $booking->load(['service', 'staff', 'customer']);
        $tz = Setting::get(SettingKey::BusinessTimezone, 'Africa/Addis_Ababa');

        return response()->json(['data' => $this->formatBooking($booking, $tz)]);
    }

    /** POST /bookings/manage/{token}/cancel */
    public function cancel(string $token): JsonResponse
    {
        $booking = $this->resolveByToken($token);
        if (! $booking) {
            return $this->tokenError();
        }

        $tz          = Setting::get(SettingKey::BusinessTimezone, 'Africa/Addis_Ababa');
        $cancelWindow = (int) Setting::get(SettingKey::CancelWindowHours, 2);

        // Check the cancellation window
        $deadline = $booking->starts_at->copy()->subHours($cancelWindow);
        if (Carbon::now()->gte($deadline)) {
            return response()->json([
                'message' => "Cancellations must be made at least {$cancelWindow} hours before the appointment.",
                'code'    => 'CANCEL_WINDOW_PASSED',
            ], 422);
        }

        $booking = $this->bookingService->cancel($booking);

        return response()->json(['data' => $this->formatBooking($booking->load(['service', 'staff', 'customer']), $tz)]);
    }

    /** POST /bookings/manage/{token}/reschedule */
    public function reschedule(string $token, RescheduleBookingRequest $request): JsonResponse
    {
        $booking = $this->resolveByToken($token);
        if (! $booking) {
            return $this->tokenError();
        }

        $tz          = Setting::get(SettingKey::BusinessTimezone, 'Africa/Addis_Ababa');
        $cancelWindow = (int) Setting::get(SettingKey::CancelWindowHours, 2);

        $deadline = $booking->starts_at->copy()->subHours($cancelWindow);
        if (Carbon::now()->gte($deadline)) {
            return response()->json([
                'message' => "Rescheduling must be done at least {$cancelWindow} hours before the appointment.",
                'code'    => 'RESCHEDULE_WINDOW_PASSED',
            ], 422);
        }

        try {
            $validated = $request->validated();
            $booking   = $this->bookingService->reschedule(
                $booking,
                (int) $validated['staff_id'],
                $validated['starts_at'],
            );

            return response()->json(['data' => $this->formatBooking($booking->load(['service', 'staff', 'customer']), $tz)]);
        } catch (SlotConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code'    => 'SLOT_CONFLICT',
            ], 409);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function resolveByToken(string $token): ?Booking
    {
        $hash    = hash('sha256', $token);
        $booking = Booking::where('manage_token_hash', $hash)->first();

        if (! $booking || ! $booking->verifyManageToken($token)) {
            return null;
        }

        return $booking;
    }

    private function tokenError(): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid or expired manage link.',
            'code'    => 'INVALID_TOKEN',
        ], 404);
    }

    private function formatBooking(Booking $booking, string $tz): array
    {
        return [
            'id'         => $booking->id,
            'reference'  => $booking->reference,
            'status'     => $booking->status->value,
            'starts_at'  => $booking->starts_at->copy()->timezone($tz)->toISOString(),
            'ends_at'    => $booking->ends_at->copy()->timezone($tz)->toISOString(),
            'service'    => ['id' => $booking->service->id, 'name' => $booking->service->name],
            'staff'      => ['id' => $booking->staff->id, 'name' => $booking->staff->display_name],
            'customer'   => ['name' => $booking->customer->name, 'phone' => $booking->customer->phone],
        ];
    }
}
