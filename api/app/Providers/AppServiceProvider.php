<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\BookingCreated;
use App\Http\Middleware\RequireRole;
use App\Listeners\DispatchBookingNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Register custom route middleware alias
        Route::aliasMiddleware('role', RequireRole::class);

        // Event → Listener wiring
        Event::listen(BookingCreated::class, DispatchBookingNotifications::class);
    }
}
