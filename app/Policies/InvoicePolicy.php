<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasSchoolContext($user) && $this->canViewFinance($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->belongsToSameSchool($user, $invoice->school_id)
            && $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->hasSchoolContext($user) && $this->canManageFinance($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->belongsToSameSchool($user, $invoice->school_id)
            && $this->canManageFinance($user);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->update($user, $invoice);
    }

    private function canViewFinance(User $user): bool
    {
        return $user->hasCapability('canManageFinance')
            || $user->hasPermission('finance.manage')
            || $user->hasPermission('transactions.view');
    }

    private function canManageFinance(User $user): bool
    {
        return $user->hasCapability('canManageFinance')
            || $user->hasPermission('finance.manage');
    }
}
