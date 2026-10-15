<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Routes that remain accessible while a temporary password is in force.
     *
     * @var list<string>
     */
    protected array $except = [
        'api/v1/auth/logout',
        'api/v1/auth/me',
        'api/v1/auth/platform-terms',
        'api/v1/auth/privacy-policy',
        'api/v1/auth/accept-platform-terms',
        'api/v1/user/change-password',
        'api/v1/license/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $user = $request->user();
        if ($user && $user->must_change_password) {
            return response()->json([
                'message' => 'You must change your temporary password before continuing.',
                'code' => 'must_change_password',
            ], 403);
        }

        return $next($request);
    }
}
