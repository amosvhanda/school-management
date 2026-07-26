<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\StudentCategory;
use Illuminate\Http\Request;

class StudentCategoryController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return StudentCategory::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageStudents', 'canManageTeachers'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['students.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Student category';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'student_categories'),
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'student_categories', $id),
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
