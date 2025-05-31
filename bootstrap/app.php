<?php

use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);

        // Register route middleware aliases here
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'ability' => CheckTokenAbility::class,
        ]);

        // Global middleware (if needed)
        // $middleware->append([
        //     HasOtherMiddleware::class,
        // ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('sanctum:prune-expired')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
