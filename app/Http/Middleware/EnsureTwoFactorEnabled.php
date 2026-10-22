<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Routes reachable while setting up 2FA or completing auth.
     *
     * @var list<string>
     */
    protected array $except = [
        'api/v1/auth/logout',
        'api/v1/auth/me',
        'api/v1/auth/two-factor',
        'api/v1/auth/two-factor/*',
        'api/v1/auth/platform-terms',
        'api/v1/auth/accept-platform-terms',
        'api/v1/user/profile',
        'api/v1/user/change-password',
        'api/v1/license/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.require_two_factor', false)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $requiredRoles = config('security.require_two_factor_roles', []);
        if (! in_array($user->roleValue(), $requiredRoles, true)) {
            return $next($request);
        }

        if (! $user->two_factor_secret || ! $user->two_factor_confirmed_at) {
            return response()->json([
                'message' => 'Two-factor authentication must be enabled for this account.',
                'code' => 'two_factor_setup_required',
            ], 403);
        }

        return $next($request);
    }
}
