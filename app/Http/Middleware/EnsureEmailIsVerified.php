<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var(env('EMAIL_VERIFICATION_REQUIRED', false), FILTER_VALIDATE_BOOL)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email address is not verified.',
                'error_code' => 'email_unverified',
            ], 409);
        }

        return $next($request);
    }
}
