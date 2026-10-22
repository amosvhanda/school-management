<?php

namespace App\Http\Requests\Api\V1\Attendance\Concerns;

use App\Models\User;
use App\Services\PermissionService;

trait AuthorizesAttendanceManage
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

        return $service->hasCapability($user, 'canManageStudents')
            || $service->hasCapability($user, 'canManageTeachers')
            || $service->hasPermission($user, 'attendance.manage');
    }
}
