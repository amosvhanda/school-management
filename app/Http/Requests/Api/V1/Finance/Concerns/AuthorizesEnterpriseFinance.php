<?php

namespace App\Http\Requests\Api\V1\Finance\Concerns;

use App\Models\User;
use App\Services\PermissionService;

trait AuthorizesEnterpriseFinance
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
            || $service->hasPermission($user, 'finance.manage');
    }
}
