<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolLicenseActive
{
    /**
     * Routes that remain accessible when a license is expired (renewal flow).
     *
     * @var list<string>
     */
    protected array $except = [
        'api/v1/license/*',
        'api/v1/auth/logout',
        'api/v1/auth/me',
        'api/v1/auth/schools',
        'api/v1/auth/switch-school',
        'api/v1/auth/platform-terms',
        'api/v1/auth/accept-platform-terms',
    ];

    public function __construct(private LicenseService $licenses) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->school_id) {
            return response()->json([
                'message' => 'Your account is not linked to a school.',
                'code' => 'school_missing',
            ], 403);
        }

        $school = School::find($user->school_id);

        if ($school && $school->status === 'deleted') {
            return response()->json([
                'message' => 'This school has been deprovisioned.',
                'code' => 'school_deleted',
            ], 403);
        }

        if ($school && $school->status !== 'active') {
            return response()->json([
                'message' => 'This school is currently suspended.',
                'code' => 'school_suspended',
            ], 403);
        }

        if (! config('license.enforcement', false)) {
            return $next($request);
        }

        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        if (! $school || ! $this->licenses->isLicensed($school)) {
            $state = $school ? $this->licenses->resolveLicenseState($school) : [
                'status' => 'none',
                'message' => 'No license found for this school.',
            ];

            return response()->json([
                'message' => $state['message'],
                'code' => 'license_'.$state['status'],
                'license' => $state,
            ], 402);
        }

        return $next($request);
    }
}
