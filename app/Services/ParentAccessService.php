<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ParentAccessService
{
    public function isParent(User $user): bool
    {
        return $user->role === UserRole::Parent;
    }

    public function canManageAnyParent(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::SchoolAdmin, UserRole::Teacher], true);
    }

    /**
     * @return Collection<int, int>
     */
    public function accessibleStudentIds(User $user): Collection
    {
        if (! $this->isParent($user)) {
            return collect();
        }

        $fromPivot = $user->students()
            ->when($user->school_id, fn ($q) => $q->where('students.school_id', $user->school_id))
            ->pluck('students.id');

        $fromGuardian = Guardian::query()
            ->where('user_id', $user->id)
            ->where('school_id', $user->school_id)
            ->with('students:id')
            ->get()
            ->flatMap(fn (Guardian $g) => $g->students->pluck('id'));

        return $fromPivot->merge($fromGuardian)->unique()->values();
    }

    public function assertCanAccessStudent(User $user, int $studentId): Student
    {
        $student = Student::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->find($studentId);

        if (! $student) {
            throw new NotFoundHttpException('Student not found.');
        }

        if ($this->canManageAnyParent($user)) {
            return $student;
        }

        if ($user->role === UserRole::Student) {
            $linked = (int) ($student->user_id ?? 0) === (int) $user->id
                || (int) ($user->student_id ?? 0) === (int) $student->id;
            if ($linked) {
                return $student;
            }
        }

        if ($this->isParent($user) && $this->accessibleStudentIds($user)->contains($studentId)) {
            return $student;
        }

        if ($this->isParent($user)) {
            throw new AccessDeniedHttpException('Parent not linked to the student.');
        }

        throw new AccessDeniedHttpException('You do not have access to this student.');
    }

    /**
     * @return Collection<int, User>
     */
    public function parentUsersForStudent(Student $student): Collection
    {
        $parents = $student->parents()->get();

        $guardianUsers = Guardian::query()
            ->where('school_id', $student->school_id)
            ->whereNotNull('user_id')
            ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        return $parents->merge($guardianUsers)->unique('id')->values();
    }
}
