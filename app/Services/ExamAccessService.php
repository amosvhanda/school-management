<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ExamAccessService
{
    public function __construct(
        private PermissionService $permissions,
    ) {}

    public function teacherFor(User $user): ?Teacher
    {
        if ($user->role !== UserRole::Teacher) {
            return null;
        }

        return $user->relationLoaded('teacher')
            ? $user->teacher
            : Teacher::query()->where('user_id', $user->id)->first();
    }

    public function canManageExams(User $user): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $this->permissions->hasPermission($user, 'exams.manage');
    }

    public function canEnterResults(User $user, Exam $exam): bool
    {
        if ($this->canManageExams($user)) {
            return true;
        }

        if (! $this->permissions->hasCapability($user, 'canEnterExamResults')) {
            return false;
        }

        $teacher = $this->teacherFor($user);
        if (! $teacher) {
            return false;
        }

        return $this->teacherTeachesExamSubject($teacher, $exam);
    }

    public function assertCanEnterResults(User $user, Exam $exam): void
    {
        if (! $this->canEnterResults($user, $exam)) {
            throw new AccessDeniedHttpException(
                'You can only enter marks for subjects you are assigned to teach.',
            );
        }
    }

    public function assertCanManageExams(User $user): void
    {
        if (! $this->canManageExams($user)) {
            throw new AccessDeniedHttpException('You do not have permission to manage examinations.');
        }
    }

    public function assertExamOpenForEntry(Exam $exam): void
    {
        if ($exam->is_published || $exam->results_approved_at) {
            throw new AccessDeniedHttpException('Results are locked after approval or publication.');
        }
    }

    /**
     * @param  Builder<Exam>  $query
     * @return Builder<Exam>
     */
    public function scopeVisibleExams(Builder $query, User $user): Builder
    {
        if ($this->canManageExams($user)) {
            return $query;
        }

        if (! $this->permissions->hasCapability($user, 'canEnterExamResults')) {
            return $query->whereRaw('1 = 0');
        }

        $teacher = $this->teacherFor($user);
        if (! $teacher) {
            return $query->whereRaw('1 = 0');
        }

        $subjectIds = $this->assignedSubjectIds($teacher);

        if ($subjectIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('subject_id', $subjectIds);
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function assignedSubjectIds(Teacher $teacher)
    {
        $fromAssignments = TeacherAssignment::query()
            ->where('school_id', $teacher->school_id)
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereNotNull('subject_id')
            ->pluck('subject_id');

        $fromSubjects = Subject::query()
            ->where('school_id', $teacher->school_id)
            ->where('teacher_id', $teacher->id)
            ->pluck('id');

        return $fromAssignments->merge($fromSubjects)->unique()->values();
    }

    private function teacherTeachesExamSubject(Teacher $teacher, Exam $exam): bool
    {
        if ((int) $teacher->school_id !== (int) $exam->school_id) {
            return false;
        }

        if (Subject::query()
            ->where('school_id', $teacher->school_id)
            ->where('id', $exam->subject_id)
            ->where('teacher_id', $teacher->id)
            ->exists()) {
            return true;
        }

        return TeacherAssignment::query()
            ->where('school_id', $teacher->school_id)
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $exam->subject_id)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($exam): void {
                $query
                    ->where('grade_level_id', $exam->grade_level_id)
                    ->orWhereNull('grade_level_id');
            })
            ->exists();
    }
}
