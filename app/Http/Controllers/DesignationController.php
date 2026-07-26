<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return Designation::class;
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
        return 'Designation';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'designations'),
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'designations', $id),
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
