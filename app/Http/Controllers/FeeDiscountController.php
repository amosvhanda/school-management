<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\FeeDiscount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeDiscountController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return FeeDiscount::class;
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
        return 'Fee discount';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'value' => array_values(array_filter([
                'required',
                'numeric',
                'min:0',
                $request->input('discount_type') === 'percent' ? 'max:100' : null,
            ])),
            'fee_category_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'student_category_id' => [
                'nullable',
                'integer',
                Rule::exists('student_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        $existing = FeeDiscount::query()
            ->where('school_id', $schoolId)
            ->find($id);
        $discountType = $request->input('discount_type', $existing?->discount_type);

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['sometimes', 'in:percent,fixed'],
            'value' => array_values(array_filter([
                'sometimes',
                'numeric',
                'min:0',
                $discountType === 'percent' ? 'max:100' : null,
            ])),
            'fee_category_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'student_category_id' => [
                'nullable',
                'integer',
                Rule::exists('student_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $query = $this->applyIndexFilters($this->schoolQuery($request), $request)
            ->with(['feeCategory:id,name', 'studentCategory:id,name']);
        $this->orderIndex($query);

        return response()->json(['data' => $query->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        $record = $this->schoolQuery($request)
            ->with(['feeCategory:id,name', 'studentCategory:id,name'])
            ->findOrFail($id);

        return response()->json(['data' => $record]);
    }
}
