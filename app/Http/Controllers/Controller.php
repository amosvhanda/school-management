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

    /**
     * Require at least one capability or permission slug.
     *
     * @param  list<string>  $capabilities
     * @param  list<string>  $permissionSlugs
     */
    protected function authorizeModuleAccess(
        Request $request,
        array $capabilities = [],
        array $permissionSlugs = [],
    ): void {
        $user = $request->user();
        abort_unless($user instanceof User, 403, 'Unauthenticated.');

        if ($user->isSuperAdmin()) {
            return;
        }

        $service = app(PermissionService::class);

        foreach ($capabilities as $capability) {
            if ($service->hasCapability($user, $capability)) {
                return;
            }
        }

        foreach ($permissionSlugs as $slug) {
            if ($service->hasPermission($user, $slug)) {
                return;
            }
        }

        abort(403, 'You do not have permission for this module.');
    }
}
