<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use App\Http\Middleware\NormalizeApiPrefix;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register route middlewares here
        $middleware->alias([
            'merchant.verified' => \App\Http\Middleware\EnsureMerchantVerified::class,
            'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'web'               => \App\Http\Middleware\SkipNgrokWarning::class,
        ]);
        // Enable CORS for all routes (configured via config/cors.php)
        $middleware->append(HandleCors::class);
        // Normalize missing /api prefix from common API paths
        $middleware->prepend(NormalizeApiPrefix::class);
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
