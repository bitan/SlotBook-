<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\Public;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Services & Staff (public, cached)
    Route::get('/services',     [Public\ServiceController::class, 'index']);
    Route::get('/staff',        [Public\StaffController::class, 'index']);

    // Availability
    Route::get('/availability/slots', [Public\AvailabilityController::class, 'slots']);
    Route::get('/availability/dates', [Public\AvailabilityController::class, 'dates']);

    // Public booking — throttled: 5 requests per IP per minute
    Route::middleware(['throttle:5,1'])->group(function () {
        Route::post('/bookings', [Public\BookingController::class, 'store']);
    });

    // Manage link — read, cancel, reschedule (token-based, no login required)
    Route::prefix('bookings/manage/{token}')->group(function () {
        Route::get('/',            [Public\BookingController::class, 'show']);
        Route::post('/cancel',     [Public\BookingController::class, 'cancel']);
        Route::post('/reschedule', [Public\BookingController::class, 'reschedule']);
    });

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {
        Route::post('/login',          [Admin\AuthController::class, 'login'])
            ->middleware('throttle:10,1');
        Route::post('/forgot-password',[Admin\AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [Admin\AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [Admin\AuthController::class, 'logout']);
            Route::get('/me',      [Admin\AuthController::class, 'me']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin routes — authenticated, role-based
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')
        ->middleware(['auth:sanctum'])
        ->group(function () {

            // Bookings — staff can see and edit only their own
            Route::apiResource('bookings', Admin\BookingController::class);

            // Services — admin only
            Route::middleware('role:admin')->group(function () {
                Route::apiResource('services', Admin\ServiceController::class);
                Route::apiResource('customers', Admin\CustomerController::class)
                    ->only(['index', 'update', 'destroy']);

                // Reports
                Route::get('/reports/monthly', [Admin\ReportController::class, 'monthly']);

                // Settings
                Route::get('/settings',  [Admin\SettingsController::class, 'index']);
                Route::put('/settings',  [Admin\SettingsController::class, 'update']);

                // Staff management
                Route::apiResource('staff', Admin\StaffController::class)
                    ->only(['index', 'store', 'update']);
                Route::get('staff/{staff}/hours',        [Admin\StaffController::class, 'hours']);
                Route::put('staff/{staff}/hours',        [Admin\StaffController::class, 'setHours']);
                Route::get('staff/{staff}/time-off',     [Admin\StaffController::class, 'timeOff']);
                Route::post('staff/{staff}/time-off',    [Admin\StaffController::class, 'addTimeOff']);
                Route::delete('staff/{staff}/time-off/{timeOffId}', [Admin\StaffController::class, 'removeTimeOff']);
            });
        });
});
