<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $staff = Staff::query()
            ->whereHas('services', fn ($q) => $q->where('services.id', $request->service_id)->where('is_active', true))
            ->where('is_active', true)
            ->get(['id', 'display_name', 'avatar_url']);

        return response()->json(['data' => $staff]);
    }
}
