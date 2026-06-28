<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function userCanManageRoles(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return app(PermissionService::class)->hasPermission($user, 'roles.manage')
            || ($user->role instanceof \App\Enums\UserRole && $user->role->canManageTeachers());
    }

    protected function authorizeRoleManagement(Request $request): void
    {
        abort_unless($this->userCanManageRoles($request->user()), 403, 'You do not have permission to manage roles.');
    }
}
