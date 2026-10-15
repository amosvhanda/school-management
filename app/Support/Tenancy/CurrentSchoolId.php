<?php

namespace App\Support\Tenancy;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CurrentSchoolId
{
    private static bool $resolving = false;

    public static function resolve(): ?int
    {
        $fromConfig = config('tenancy.current_school_id');
        if (is_numeric($fromConfig) && (int) $fromConfig > 0) {
            return (int) $fromConfig;
        }

        if (self::$resolving) {
            return null;
        }

        self::$resolving = true;

        try {
            $user = Auth::user();
            if ($user instanceof User
                && $user->role !== UserRole::SuperAdmin
                && $user->school_id !== null) {
                return (int) $user->school_id;
            }
        } finally {
            self::$resolving = false;
        }

        return null;
    }

    /**
     * Authenticated callers without a tenant school must not see unscoped rows.
     */
    public static function shouldFailClosed(): bool
    {
        return Auth::user() instanceof User && self::resolve() === null;
    }
}
