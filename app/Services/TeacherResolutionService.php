<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\ClassSubstitution;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Canonical User ↔ Teacher linking and class ownership helpers.
 *
 * Prefer teachers.user_id; fall back to school-scoped email and persist the link.
 */
class TeacherResolutionService
{
    public function resolveForUser(User $user, bool $persistLink = true): ?Teacher
    {
        $teacher = $user->relationLoaded('teacher')
            ? $user->teacher
            : $user->teacher()->first();

        if ($teacher) {
            return $teacher;
        }

        if (! $user->email && ! $user->id) {
            return null;
        }

        $teacher = Teacher::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->email) {
                    $q->orWhere('email', $user->email);
                }
            })
            ->orderByRaw('case when user_id = ? then 0 else 1 end', [$user->id])
            ->first();

        if ($teacher && $persistLink && (int) $teacher->user_id !== (int) $user->id) {
            $teacher->forceFill([
                'user_id' => $user->id,
                'email' => $user->email ?: $teacher->email,
            ])->save();
            $user->setRelation('teacher', $teacher);
        }

        return $teacher;
    }

    /**
     * Active class IDs this teacher owns via assignments (null class_id excluded).
     *
     * @return Collection<int, int>
     */
    public function assignedClassIds(Teacher $teacher): Collection
    {
        return TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereNotNull('class_id')
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    public function ownsClass(Teacher $teacher, mixed $classId): bool
    {
        if (! $classId) {
            return false;
        }

        $classId = (int) $classId;

        if ($this->assignedClassIds($teacher)->contains($classId)) {
            return true;
        }

        if (ClassModel::query()
            ->where('id', $classId)
            ->where('teacher_id', $teacher->id)
            ->exists()) {
            return true;
        }

        return ClassSubstitution::query()
            ->where('substitute_teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->whereDate('date', now()->toDateString())
            ->whereIn('status', ['accepted', 'assigned', 'pending'])
            ->exists();
    }

    public function assertOwnsClass(Teacher $teacher, mixed $classId): void
    {
        abort_unless($this->ownsClass($teacher, $classId), 403, 'You are not assigned to this class.');
    }

    public function ownsSubject(Teacher $teacher, mixed $subjectId, ?int $classId = null): bool
    {
        if (! $subjectId) {
            return false;
        }

        $subjectId = (int) $subjectId;

        $query = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->where('subject_id', $subjectId);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($query->exists()) {
            return true;
        }

        return Subject::query()
            ->where('id', $subjectId)
            ->where('teacher_id', $teacher->id)
            ->when($teacher->school_id, fn ($q) => $q->where('school_id', $teacher->school_id))
            ->exists();
    }

    public function assertOwnsSubject(Teacher $teacher, mixed $subjectId, ?int $classId = null): void
    {
        abort_unless(
            $this->ownsSubject($teacher, $subjectId, $classId),
            403,
            'You are not assigned to teach this subject.',
        );
    }
}
