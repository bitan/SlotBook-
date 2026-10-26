<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use App\Models\WorkingHours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(): JsonResponse
    {
        $staff = Staff::with(['user', 'services', 'workingHours'])->get();

        return response()->json(['data' => $staff]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'display_name' => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8'],
            'service_ids'  => ['nullable', 'array'],
            'service_ids.*'=> ['integer', 'exists:services,id'],
        ]);

        $staff = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => UserRole::Staff->value,
            ]);

            $staff = Staff::create([
                'user_id'      => $user->id,
                'display_name' => $validated['display_name'],
            ]);

            if (! empty($validated['service_ids'])) {
                $staff->services()->sync($validated['service_ids']);
            }

            return $staff->load(['user', 'services']);
        });

        return response()->json(['data' => $staff], 201);
    }

    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['sometimes', 'string', 'max:100'],
            'is_active'    => ['sometimes', 'boolean'],
            'service_ids'  => ['sometimes', 'array'],
            'service_ids.*'=> ['integer', 'exists:services,id'],
        ]);

        if (isset($validated['service_ids'])) {
            $staff->services()->sync($validated['service_ids']);
        }

        $staff->update(\Arr::only($validated, ['display_name', 'is_active']));

        return response()->json(['data' => $staff->fresh()->load(['user', 'services'])]);
    }

    // ── Working hours ──────────────────────────────────────────────────────────

    public function hours(Staff $staff): JsonResponse
    {
        return response()->json(['data' => $staff->workingHours]);
    }

    public function setHours(Request $request, Staff $staff): JsonResponse
    {
        $request->validate([
            'hours'            => ['required', 'array'],
            'hours.*.weekday'  => ['required', 'integer', 'min:0', 'max:6'],
            'hours.*.start_time' => ['required', 'date_format:H:i:s'],
            'hours.*.end_time'   => ['required', 'date_format:H:i:s'],
        ]);

        // Replace all hours for this staff member atomically
        DB::transaction(function () use ($staff, $request) {
            $staff->workingHours()->delete();
            foreach ($request->hours as $row) {
                $staff->workingHours()->create([
                    'weekday'    => $row['weekday'],
                    'start_time' => $row['start_time'],
                    'end_time'   => $row['end_time'],
                ]);
            }
        });

        return response()->json(['data' => $staff->workingHours()->get()]);
    }

    // ── Time off ───────────────────────────────────────────────────────────────

    public function timeOff(Staff $staff): JsonResponse
    {
        return response()->json(['data' => $staff->timeOff()->orderBy('starts_at')->get()]);
    }

    public function addTimeOff(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date', 'before:ends_at'],
            'ends_at'   => ['required', 'date', 'after:starts_at'],
            'reason'    => ['nullable', 'string', 'max:200'],
        ]);

        $timeOff = $staff->timeOff()->create($validated);

        return response()->json(['data' => $timeOff], 201);
    }

    public function removeTimeOff(Staff $staff, int $timeOffId): JsonResponse
    {
        $staff->timeOff()->findOrFail($timeOffId)->delete();

        return response()->json(null, 204);
    }
}
