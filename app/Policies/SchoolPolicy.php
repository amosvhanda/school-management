<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolPolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function view(User $user, School $school): bool
    {
        return $this->belongsToSameSchool($user, $school->id);
    }

    public function update(User $user, School $school): bool
    {
        return $this->belongsToSameSchool($user, $school->id)
            && $user->role?->canManageTeachers();
    }
}
