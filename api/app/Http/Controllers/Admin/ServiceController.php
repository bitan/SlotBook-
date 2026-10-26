<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Service::withTrashed()->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string'],
            'duration_min' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_min'   => ['nullable', 'integer', 'min:0', 'max:120'],
            'price'        => ['required', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ]);

        $service = Service::create($validated);

        return response()->json(['data' => $service], 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'name'         => ['sometimes', 'string', 'max:100'],
            'description'  => ['sometimes', 'nullable', 'string'],
            'duration_min' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'buffer_min'   => ['sometimes', 'integer', 'min:0', 'max:120'],
            'price'        => ['sometimes', 'integer', 'min:0'],
            'is_active'    => ['sometimes', 'boolean'],
        ]);

        $service->update($validated);

        return response()->json(['data' => $service->fresh()]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete(); // soft delete — preserves history

        return response()->json(null, 204);
    }
}
