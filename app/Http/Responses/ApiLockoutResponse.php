<?php

namespace App\Http\Responses;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;
use Laravel\Fortify\LoginRateLimiter;

class ApiLockoutResponse implements LockoutResponseContract
{
    public function __construct(private LoginRateLimiter $limiter) {}

    public function toResponse(Request $request): JsonResponse
    {
        $seconds = $this->limiter->availableIn($request);

        return response()->json([
            'message' => trans('auth.throttle', ['seconds' => $seconds]),
            'errors' => [
                'email' => [trans('auth.throttle', ['seconds' => $seconds])],
            ],
        ], 429);
    }
}
