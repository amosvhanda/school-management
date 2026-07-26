<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use App\Services\ParentAccessService;
use App\Services\StudentResolutionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Parents/students use portal endpoints, not the staff student index.
        if (in_array($user->role, [UserRole::Student, UserRole::Parent], true)) {
            return false;
        }

        return $this->hasSchoolContext($user);
    }

    public function view(User $user, Student $student): bool
    {
        if (! $this->belongsToSameSchool($user, $student->school_id)) {
            return false;
        }

        if ($user->role === UserRole::Student) {
            return app(StudentResolutionService::class)->isLinkedTo($user, $student);
        }

        if ($user->role === UserRole::Parent) {
            return app(ParentAccessService::class)
                ->accessibleStudentIds($user)
                ->contains((int) $student->id);
        }

        return true;
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
