<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait ChecksSchoolOwnership
{
    protected function belongsToSameSchool(User $user, ?int $schoolId): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->school_id === null || $schoolId === null) {
            return false;
        }

        return (int) $user->school_id === (int) $schoolId;
    }

    protected function hasSchoolContext(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin || $user->school_id !== null;
    }
}
