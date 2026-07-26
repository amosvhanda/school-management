<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;

/**
 * Canonical User ↔ Student linking.
 *
 * Prefer students.user_id; fall back to school-scoped email and persist the link.
 */
class StudentResolutionService
{
    public function resolveForUser(User $user, bool $persistLink = true): ?Student
    {
        $student = $user->relationLoaded('student')
            ? $user->student
            : $user->student()->first();

        if ($student) {
            return $student;
        }

        if (! $user->email && ! $user->id) {
            return null;
        }

        $student = Student::query()
            ->when($user->school_id, fn ($q) => $q->where('school_id', $user->school_id))
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->email) {
                    $q->orWhere('email', $user->email);
                }
            })
            ->orderByRaw('case when user_id = ? then 0 else 1 end', [$user->id])
            ->first();

        if ($student && $persistLink && (int) $student->user_id !== (int) $user->id) {
            $student->forceFill([
                'user_id' => $user->id,
                'email' => $user->email ?: $student->email,
            ])->save();
            $user->setRelation('student', $student);
        }

        return $student;
    }

    public function isLinkedTo(User $user, Student $student): bool
    {
        $resolved = $this->resolveForUser($user);

        return $resolved !== null && (int) $resolved->id === (int) $student->id;
    }
}
