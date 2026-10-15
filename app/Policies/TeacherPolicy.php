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
        return $this->hasSchoolContext($user)
            && (
                $user->hasCapability('canManageTeachers')
                || $user->hasPermission('hr.manage')
            );
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $this->belongsToSameSchool($user, $teacher->school_id)
            && (
                $user->hasCapability('canManageTeachers')
                || $user->hasPermission('hr.manage')
            );
    }

    public function create(User $user): bool
    {
        return $this->hasSchoolContext($user)
            && $user->hasCapability('canManageTeachers');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $this->belongsToSameSchool($user, $teacher->school_id)
            && $user->hasCapability('canManageTeachers');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $this->update($user, $teacher);
    }
}
