<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\IncomeHead;
use Illuminate\Http\Request;

class IncomeHeadController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return IncomeHead::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageFinance'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['finance.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Income head';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'income_heads'),
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'income_heads', $id),
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
