<?php

namespace App\Http\Requests\Api\V1\Finance\Concerns;

use App\Models\User;
use App\Services\PermissionService;

trait AuthorizesPayrollAccess
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $service = app(PermissionService::class);

        return $service->hasCapability($user, 'canManageFinance')
            || $service->hasCapability($user, 'canManageTeachers')
            || $service->hasPermission($user, 'finance.manage')
            || $service->hasPermission($user, 'hr.manage');
    }
}
