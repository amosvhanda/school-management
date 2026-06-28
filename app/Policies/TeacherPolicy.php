<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasSchoolContext($user);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $this->belongsToSameSchool($user, $teacher->school_id);
    }

    public function create(User $user): bool
    {
        return $user->role?->canManageTeachers() && $this->hasSchoolContext($user);
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $this->belongsToSameSchool($user, $teacher->school_id)
            && $user->role?->canManageTeachers();
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $this->update($user, $teacher);
    }
}
