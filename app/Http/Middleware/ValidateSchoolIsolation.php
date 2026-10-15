<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSchoolIsolation
{
    /**
     * Platform operators may only use auth/profile/license and admin/platform APIs.
     *
     * @var list<string>
     */
    private const SUPER_ADMIN_PREFIXES = [
        'api/v1/auth/',
        'api/v1/license/',
        'api/v1/user/',
        'api/v1/admin/',
        'api/v1/platform/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->role === UserRole::SuperAdmin) {
            if ($this->isSuperAdminAllowedPath($request)) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Platform administrators cannot access school records through school APIs.',
            ], 403);
        }

        if (! $user->school_id) {
            return response()->json([
                'message' => 'User must be associated with a school',
            ], 403);
        }

        if ($request->is('api/v1/auth/switch-school')) {
            return $next($request);
        }

        foreach ($this->collectSchoolIds($request->all()) as $schoolId) {
            if ($schoolId !== (int) $user->school_id) {
                return response()->json([
                    'message' => 'Cannot access resources from another school',
                ], 403);
            }
        }

        return $next($request);
    }

    private function isSuperAdminAllowedPath(Request $request): bool
    {
        $path = $request->path();

        foreach (self::SUPER_ADMIN_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function collectSchoolIds(array $data): array
    {
        $ids = [];

        foreach ($data as $key => $value) {
            if ($key === 'school_id' && $value !== null && $value !== '') {
                $ids[] = (int) $value;
            } elseif (is_array($value)) {
                $ids = array_merge($ids, $this->collectSchoolIds($value));
            }
        }

        return $ids;
    }
}
