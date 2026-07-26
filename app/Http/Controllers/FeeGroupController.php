<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\FeeGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeGroupController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return FeeGroup::class;
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
        return 'Fee group';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'fee_groups'),
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'fee_category_ids' => ['nullable', 'array'],
            'fee_category_ids.*' => [
                'integer',
                Rule::exists('fee_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'name' => $this->uniqueNameRule($schoolId, 'fee_groups', $id),
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'fee_category_ids' => ['nullable', 'array'],
            'fee_category_ids.*' => [
                'integer',
                Rule::exists('fee_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $query = $this->applyIndexFilters($this->schoolQuery($request), $request)
            ->with(['categories:id,name'])
            ->withCount('categories');
        $this->orderIndex($query);

        return response()->json(['data' => $query->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        $record = $this->schoolQuery($request)
            ->with(['categories:id,name'])
            ->withCount('categories')
            ->findOrFail($id);

        return response()->json(['data' => $record]);
    }

    protected function afterStore(Request $request, Model $record): void
    {
        $this->syncCategories($request, $record);
    }

    protected function afterUpdate(Request $request, Model $record): void
    {
        $this->syncCategories($request, $record);
    }

    protected function syncCategories(Request $request, Model $record): void
    {
        if (! $request->exists('fee_category_ids')) {
            return;
        }

        /** @var FeeGroup $record */
        $record->categories()->sync($request->input('fee_category_ids', []));
    }
}
