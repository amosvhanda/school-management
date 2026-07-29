<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\CaptureAuditContext;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureSchoolLicenseActive;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\ValidateSchoolIsolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            'verified' => EnsureEmailIsVerified::class,
            'school.isolated' => ValidateSchoolIsolation::class,
            'school.licensed' => EnsureSchoolLicenseActive::class,
            'password.changed' => EnsurePasswordChanged::class,
            'super_admin' => EnsureSuperAdmin::class,
        ]);

        $middleware->api(append: [
            CaptureAuditContext::class,
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof DomainException) {
                return response()->json($e->toArray(), $e->status);
            }

            if ($e instanceof InvalidFilterQuery
                || $e instanceof InvalidSortQuery
                || $e instanceof InvalidIncludeQuery
                || $e instanceof InvalidFieldQuery) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['query' => [$e->getMessage()]],
                ], 422);
            }

            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof HttpExceptionInterface
                || $e instanceof ModelNotFoundException) {
                return null;
            }

            if ($e instanceof UniqueConstraintViolationException) {
                return response()->json([
                    'message' => 'A matching record already exists.',
                    'errors' => ['record' => ['Duplicate entry — this record already exists.']],
                ], 422);
            }

            if ($e instanceof QueryException) {
                $sqlState = $e->errorInfo[0] ?? null;
                // Integrity constraint (FK / unique / check)
                if (in_array($sqlState, ['23000', '23503', '23505'], true)) {
                    return response()->json([
                        'message' => 'The request conflicts with existing data.',
                        'errors' => ['record' => ['Database constraint violation. Check related records and try again.']],
                    ], 422);
                }
            }

            if (! config('app.debug')) {
                return response()->json([
                    'message' => 'An unexpected error occurred. Please try again.',
                ], 500);
            }

            return null;
        });
    })->create();
