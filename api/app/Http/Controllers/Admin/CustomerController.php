<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Customer::query()->with(['bookings' => fn ($q) => $q->latest('starts_at')->limit(5)]);

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('email', 'like', $term));
        }

        $customers = $query->orderBy('name')->paginate($request->input('per_page', 20));

        return response()->json($customers);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer->update($validated);

        return response()->json(['data' => $customer->fresh()]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        // GDPR-style: anonymise rather than hard delete so booking history totals stay accurate
        $customer->update([
            'name'  => 'Deleted Customer',
            'phone' => 'deleted_' . $customer->id,
            'email' => null,
            'notes' => null,
        ]);

        return response()->json(null, 204);
    }
}
