<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return LeaveType::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageTeachers'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['hr.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Leave type';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'leave_types'),
            'code' => ['nullable', 'string', 'max:50'],
            'default_days' => ['nullable', 'integer', 'min:0'],
            'is_paid' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'leave_types', $id),
            'code' => ['nullable', 'string', 'max:50'],
            'default_days' => ['nullable', 'integer', 'min:0'],
            'is_paid' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
