<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;

class JournalEntryPolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasSchoolContext($user) && $this->canManageFinance($user);
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $this->belongsToSameSchool($user, $entry->school_id)
            && $this->canManageFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->hasSchoolContext($user) && $this->canManageFinance($user);
    }

    public function update(User $user, JournalEntry $entry): bool
    {
        return $this->belongsToSameSchool($user, $entry->school_id)
            && $this->canManageFinance($user);
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $this->update($user, $entry);
    }

    private function canManageFinance(User $user): bool
    {
        return $user->hasCapability('canManageFinance')
            || $user->hasPermission('finance.manage');
    }
}
