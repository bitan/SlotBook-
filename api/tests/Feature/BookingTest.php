<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;

// Feature tests assume a real DB via RefreshDatabase.
// They test the full HTTP layer including overlap prevention.

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = Service::factory()->create([
        'duration_min' => 60,
        'buffer_min'   => 15,
        'is_active'    => true,
    ]);

    $this->staffUser = User::factory()->create(['role' => 'staff']);
    $this->staff     = Staff::factory()->create([
        'user_id'   => $this->staffUser->id,
        'is_active' => true,
    ]);
    $this->staff->services()->attach($this->service);
});

it('creates a booking and returns a reference', function () {
    $starts = Carbon::now()->addHours(3)->startOfHour()->utc()->toISOString();

    $response = $this->postJson('/api/v1/bookings', [
        'service_id'     => $this->service->id,
        'staff_id'       => $this->staff->id,
        'starts_at'      => $starts,
        'customer_name'  => 'Test Customer',
        'customer_phone' => '+251911000001',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['reference', 'status', 'starts_at', 'ends_at']]);

    expect($response->json('data.status'))->toBeIn(['pending', 'confirmed']);
});

it('returns 409 when two requests try to book the same slot', function () {
    $starts = Carbon::now()->addHours(3)->startOfHour()->utc()->toISOString();

    $payload = [
        'service_id'     => $this->service->id,
        'staff_id'       => $this->staff->id,
        'starts_at'      => $starts,
        'customer_name'  => 'Customer One',
        'customer_phone' => '+251911000002',
    ];

    // First booking succeeds
    $this->postJson('/api/v1/bookings', $payload)->assertStatus(201);

    // Second attempt for the same slot fails
    $payload['customer_phone'] = '+251911000003';
    $payload['customer_name']  = 'Customer Two';

    $this->postJson('/api/v1/bookings', $payload)
        ->assertStatus(409)
        ->assertJson(['code' => 'SLOT_CONFLICT']);
});

it('blocks slot within buffer of an existing booking', function () {
    // Existing booking at 10:00, duration 60 min, buffer 15 min → blocked until 11:15
    $startsAt = Carbon::now()->addHours(3)->setTime(10, 0)->utc();
    $endsAt   = $startsAt->copy()->addMinutes($this->service->duration_min);

    Booking::factory()->create([
        'service_id' => $this->service->id,
        'staff_id'   => $this->staff->id,
        'starts_at'  => $startsAt,
        'ends_at'    => $endsAt,
        'status'     => BookingStatus::Confirmed->value,
    ]);

    // Try 10:30 — starts during existing booking → conflict
    $conflictAt = $startsAt->copy()->addMinutes(30)->toISOString();

    $this->postJson('/api/v1/bookings', [
        'service_id'     => $this->service->id,
        'staff_id'       => $this->staff->id,
        'starts_at'      => $conflictAt,
        'customer_name'  => 'Another Customer',
        'customer_phone' => '+251911000004',
    ])->assertStatus(409);

    // Try 11:15 — after buffer — should succeed
    $afterBuffer = $startsAt->copy()->addMinutes(75)->toISOString(); // 60 duration + 15 buffer

    $this->postJson('/api/v1/bookings', [
        'service_id'     => $this->service->id,
        'staff_id'       => $this->staff->id,
        'starts_at'      => $afterBuffer,
        'customer_name'  => 'Clean Customer',
        'customer_phone' => '+251911000005',
    ])->assertStatus(201);
});

it('customer can cancel within the window using manage token', function () {
    $manageToken = \Illuminate\Support\Str::random(64);
    $starts      = Carbon::now()->addHours(5)->utc();

    $customer = Customer::factory()->create();
    $booking  = Booking::factory()->create([
        'customer_id'       => $customer->id,
        'service_id'        => $this->service->id,
        'staff_id'          => $this->staff->id,
        'starts_at'         => $starts,
        'ends_at'           => $starts->copy()->addMinutes(60),
        'status'            => BookingStatus::Confirmed->value,
        'manage_token_hash' => hash('sha256', $manageToken),
    ]);

    $this->postJson("/api/v1/bookings/manage/{$manageToken}/cancel")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');
});

it('token is rejected for a booking in terminal state', function () {
    $manageToken = \Illuminate\Support\Str::random(64);
    $starts      = Carbon::now()->addHours(5)->utc();

    $customer = Customer::factory()->create();
    $booking  = Booking::factory()->create([
        'customer_id'       => $customer->id,
        'service_id'        => $this->service->id,
        'staff_id'          => $this->staff->id,
        'starts_at'         => $starts,
        'ends_at'           => $starts->copy()->addMinutes(60),
        'status'            => BookingStatus::Cancelled->value, // already cancelled
        'manage_token_hash' => hash('sha256', $manageToken),
    ]);

    $this->getJson("/api/v1/bookings/manage/{$manageToken}")
        ->assertStatus(404)
        ->assertJson(['code' => 'INVALID_TOKEN']);
});

it('staff member cannot see another staff member bookings', function () {
    // Create a second staff member with their own booking
    $otherUser  = User::factory()->create(['role' => 'staff']);
    $otherStaff = Staff::factory()->create(['user_id' => $otherUser->id, 'is_active' => true]);
    $otherStaff->services()->attach($this->service);

    $starts  = Carbon::now()->addHours(3)->utc();
    $booking = Booking::factory()->create([
        'service_id' => $this->service->id,
        'staff_id'   => $otherStaff->id,
        'starts_at'  => $starts,
        'ends_at'    => $starts->copy()->addMinutes(60),
        'status'     => BookingStatus::Confirmed->value,
    ]);

    // Login as first staff member
    $token = $this->staffUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/admin/bookings');
    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->not->toContain($booking->id);
});
