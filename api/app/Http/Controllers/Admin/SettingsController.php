<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Setting::allTyped()]);
    }

    public function update(Request $request): JsonResponse
    {
        $allowedKeys = array_column(SettingKey::cases(), 'value');

        $validated = $request->validate([
            'settings'       => ['required', 'array'],
            'settings.*.key' => ['required', 'in:' . implode(',', $allowedKeys)],
            'settings.*.value' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['settings'] as $entry) {
            $key = SettingKey::from($entry['key']);
            Setting::set($key, $entry['value'] ?? '');
        }

        return response()->json(['data' => Setting::allTyped()]);
    }
}
