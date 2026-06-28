<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasSchoolContext($user);
    }

    public function view(User $user, Student $student): bool
    {
        return $this->belongsToSameSchool($user, $student->school_id);
    }

    public function create(User $user): bool
    {
        return $user->hasCapability('canManageStudents') && $this->hasSchoolContext($user);
    }

    public function update(User $user, Student $student): bool
    {
        return $this->belongsToSameSchool($user, $student->school_id)
            && $user->hasCapability('canManageStudents');
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->belongsToSameSchool($user, $student->school_id)
            && $user->hasCapability('canManageTeachers');
    }
}
