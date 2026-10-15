<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use ChecksSchoolOwnership, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasSchoolContext($user) && $this->canViewFinance($user);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->belongsToSameSchool($user, $payment->school_id)
            && $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->hasSchoolContext($user) && $this->canManageFinance($user);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->belongsToSameSchool($user, $payment->school_id)
            && $this->canManageFinance($user);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
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
