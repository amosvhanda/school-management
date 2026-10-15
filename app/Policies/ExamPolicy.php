<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use App\Services\ExamAccessService;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamPolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function __construct(
        private ExamAccessService $examAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->hasSchoolContext($user)
            && (
                $this->examAccess->canManageExams($user)
                || $user->hasCapability('canEnterExamResults')
                || $user->hasCapability('isStaff')
                || $user->hasPermission('exams.enter_results')
            );
    }

    public function view(User $user, Exam $exam): bool
    {
        if (! $this->belongsToSameSchool($user, $exam->school_id)) {
            return false;
        }

        return $this->examAccess->canManageExams($user)
            || $this->examAccess->canEnterResults($user, $exam);
    }

    public function create(User $user): bool
    {
        return $this->hasSchoolContext($user)
            && $this->examAccess->canManageExams($user);
    }

    public function update(User $user, Exam $exam): bool
    {
        return $this->belongsToSameSchool($user, $exam->school_id)
            && $this->examAccess->canManageExams($user);
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }

    public function enterResults(User $user, Exam $exam): bool
    {
        return $this->belongsToSameSchool($user, $exam->school_id)
            && $this->examAccess->canEnterResults($user, $exam);
    }

    public function approveResults(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }

    public function publish(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }
}
