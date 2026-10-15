<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Require the user to have at least one of the given permission slugs.
     * Usage: middleware('permission:students.manage,library.manage')
     *
     * @param  string  ...$slugs
     */
    public function handle(Request $request, Closure $next, string ...$slugs): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $service = app(PermissionService::class);

        foreach ($slugs as $slug) {
            $slug = trim($slug);
            if ($slug !== '' && $service->hasPermission($user, $slug)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'You do not have permission for this module.',
        ], 403);
    }
}
