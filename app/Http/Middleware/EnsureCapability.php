<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCapability
{
    /**
     * Require the user to have at least one of the given capabilities.
     * Usage: middleware('capability:canManageStudents,canManageTeachers')
     *
     * @param  string  ...$capabilities
     */
    public function handle(Request $request, Closure $next, string ...$capabilities): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $service = app(PermissionService::class);

        foreach ($capabilities as $capability) {
            $capability = trim($capability);
            if ($capability !== '' && $service->hasCapability($user, $capability)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'You do not have permission for this module.',
        ], 403);
    }
}
