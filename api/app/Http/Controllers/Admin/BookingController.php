<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\SettingKey;
use App\Exceptions\SlotConflictException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date'     => ['nullable', 'date_format:Y-m-d'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'status'   => ['nullable', 'in:pending,confirmed,completed,cancelled,no_show'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $tz   = Setting::get(SettingKey::BusinessTimezone, 'Africa/Addis_Ababa');

        $query = Booking::query()->with(['customer', 'service', 'staff']);

        // Staff can only see their own bookings (FR-10)
        if ($user->isStaff()) {
            $query->where('staff_id', $user->staff->id);
        } elseif ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('date')) {
            $date  = \Carbon\Carbon::parse($request->date, $tz);
            $start = $date->copy()->startOfDay()->utc();
            $end   = $date->copy()->endOfDay()->utc();
            $query->whereBetween('starts_at', [$start, $end]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('starts_at')->paginate($request->input('per_page', 20));

        return response()->json($bookings->through(fn ($b) => [
            'id'         => $b->id,
            'reference'  => $b->reference,
            'status'     => $b->status->value,
            'starts_at'  => $b->starts_at->copy()->timezone($tz)->toISOString(),
            'ends_at'    => $b->ends_at->copy()->timezone($tz)->toISOString(),
            'service'    => ['id' => $b->service->id, 'name' => $b->service->name],
            'staff'      => ['id' => $b->staff->id, 'name' => $b->staff->display_name],
            'customer'   => [
                'id'    => $b->customer->id,
                'name'  => $b->customer->name,
                'phone' => $b->customer->phone,
                'email' => $b->customer->email,
            ],
            'note'       => $b->note,
            'source'     => $b->source->value,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id'     => ['required', 'integer', 'exists:services,id'],
            'staff_id'       => ['required', 'integer', 'exists:staff,id'],
            'starts_at'      => ['required', 'date'],
            'customer_name'  => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'note'           => ['nullable', 'string', 'max:500'],
        ]);

        $validated['source'] = 'manual';

        try {
            ['booking' => $booking] = $this->bookingService->create($validated);

            return response()->json(['data' => ['id' => $booking->id, 'reference' => $booking->reference]], 201);
        } catch (SlotConflictException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'SLOT_CONFLICT'], 409);
        }
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        // Staff can only edit their own bookings
        if ($user->isStaff() && $booking->staff_id !== $user->staff->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status'    => ['sometimes', 'in:pending,confirmed,completed,cancelled,no_show'],
            'note'      => ['sometimes', 'nullable', 'string', 'max:500'],
            'staff_id'  => ['sometimes', 'integer', 'exists:staff,id'],
            'starts_at' => ['sometimes', 'date'],
        ]);

        // If rescheduling
        if (isset($validated['starts_at'])) {
            try {
                $booking = $this->bookingService->reschedule(
                    $booking,
                    (int) ($validated['staff_id'] ?? $booking->staff_id),
                    $validated['starts_at'],
                );
            } catch (SlotConflictException $e) {
                return response()->json(['message' => $e->getMessage(), 'code' => 'SLOT_CONFLICT'], 409);
            }
        }

        if (isset($validated['status'])) {
            $booking->update(['status' => $validated['status']]);
        }

        if (array_key_exists('note', $validated)) {
            $booking->update(['note' => $validated['note']]);
        }

        return response()->json(['data' => $booking->fresh()]);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $this->bookingService->cancel($booking);

        return response()->json(null, 204);
    }
}
