<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'staff_id'   => ['nullable', 'integer', 'exists:staff,id'],
            'date'       => ['required', 'date_format:Y-m-d'],
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $staffId = isset($validated['staff_id']) ? (int) $validated['staff_id'] : null;

        $slots = $this->availability->getSlots($service, $staffId, $validated['date']);

        return response()->json([
            'data' => $slots->map(fn ($s) => [
                'starts_at' => $s['starts_at']->toISOString(),
                'staff_id'  => $s['staff_id'],
            ]),
        ]);
    }

    public function dates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'staff_id'   => ['nullable', 'integer', 'exists:staff,id'],
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $staffId = isset($validated['staff_id']) ? (int) $validated['staff_id'] : null;

        $dates = $this->availability->getAvailableDates($service, $staffId);

        return response()->json(['data' => $dates]);
    }
}
