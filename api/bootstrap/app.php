<?php

use App\Console\Commands\SendAppointmentReminders;
use App\Exceptions\SlotConflictException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command(SendAppointmentReminders::class)->everyFifteenMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (SlotConflictException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'code'    => 'SLOT_CONFLICT',
            ], 409);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'message' => 'Validation failed.',
                'code'    => 'VALIDATION_ERROR',
                'errors'  => $e->errors(),
            ], 422);
        });
    })
    ->create();
