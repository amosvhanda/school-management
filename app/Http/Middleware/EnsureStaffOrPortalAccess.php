<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticated API routes are staff-only by default.
 * Parent/student tokens may only use shared auth/profile/license paths and their portals.
 */
class EnsureStaffOrPortalAccess
{
    /**
     * Path prefixes (relative to the request path, e.g. "api/v1/auth/me") allowed for non-staff.
     *
     * @var list<string>
     */
    private const NON_STAFF_PREFIXES = [
        'api/v1/auth/',
        'api/v1/license/',
        'api/v1/user/',
        'api/v1/student-portal/',
        'api/v1/parent/portal/',
    ];

    /**
     * GET-only school routes that already enforce parent/student ownership in policies.
     *
     * @var list<string>
     */
    private const NON_STAFF_GET_PATTERNS = [
        '#^api/v1/students/\d+$#',
        '#^api/v1/students/\d+/results/download$#',
        '#^api/v1/parents/\d+/children$#',
        '#^api/v1/timetable$#',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isSuperAdmin() || app(PermissionService::class)->hasCapability($user, 'isStaff')) {
            return $next($request);
        }

        $path = $request->path();

        foreach (self::NON_STAFF_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        if ($request->isMethod('GET')) {
            foreach (self::NON_STAFF_GET_PATTERNS as $pattern) {
                if (preg_match($pattern, $path) === 1) {
                    return $next($request);
                }
            }
        }

        if ($request->isMethod('POST') && $path === 'api/v1/uploads') {
            $directory = trim((string) $request->input('directory', 'uploads'), '/');
            if ($directory === 'avatars' || str_ends_with($directory, '/avatars')) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'You do not have permission for this module.',
        ], 403);
    }
}
