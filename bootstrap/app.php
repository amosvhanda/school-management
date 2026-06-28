<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only backend: never redirect unauthenticated requests to a missing web login route.
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/csrf-cookie',
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'school.isolated' => \App\Http\Middleware\ValidateSchoolIsolation::class,
            'school.licensed' => \App\Http\Middleware\EnsureSchoolLicenseActive::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CaptureAuditContext::class,
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
