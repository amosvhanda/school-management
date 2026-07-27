<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPaginatedList;
use App\Models\ClassModel;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeeStructureController extends Controller
{
    use RespondsWithPaginatedList;

    public function index(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $query = FeeStructure::query()->with(['feeCategory:id,name,is_active', 'classModel:id,name']);

        $filters = is_array($request->input('filter')) ? $request->input('filter') : [];
        $classId = $request->input('class_id', $filters['class_id'] ?? null);
        $feeCategoryId = $request->input('fee_category_id', $filters['fee_category_id'] ?? null);
        $category = $request->input('category', $filters['category'] ?? null);
        $currency = $request->input('currency', $filters['currency'] ?? null);
        $search = $request->input('search', $filters['search'] ?? null);

        if (filled($classId)) {
            $query->where('class_id', $classId);
        }
        if (filled($feeCategoryId)) {
            $query->where('fee_category_id', $feeCategoryId);
        }
        if (filled($category)) {
            $query->where('category', $category);
        }
        if (filled($currency)) {
            $query->where('currency', $currency);
        }
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('class_name')->orderBy('category');

        return $this->indexResponse($request, $query);
    }

    public function store(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $schoolId = $request->user()->school_id;

        $validator = Validator::make($request->all(), [
            'class' => 'nullable|string',
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'fee_category_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'category' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|in:USD,ZWG,ZWL',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $request->filled('fee_category_id') && ! $request->filled('category')) {
                $validator->errors()->add('fee_category_id', 'Select a fee category.');
            }
            if (! $request->filled('class_id') && ! $request->filled('class')) {
                $validator->errors()->add('class_id', 'Select a class.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$classId, $className] = $this->resolveClass($request, $schoolId);
        [$categoryId, $categoryName] = $this->resolveCategory($request, $schoolId);

        $schoolCurrency = $request->user()->school?->getDefaultCurrency() ?? 'USD';
        $currency = $request->filled('currency')
            ? ($request->currency === 'ZWL' ? 'ZWG' : $request->currency)
            : $schoolCurrency;

        $feeStructure = FeeStructure::create([
            'class_id' => $classId,
            'class_name' => $className,
            'fee_category_id' => $categoryId,
            'category' => $categoryName,
            'amount' => $request->amount,
            'currency' => $currency,
            'school_id' => $schoolId,
        ]);

        return response()->json([
            'data' => $feeStructure->load(['feeCategory:id,name,is_active', 'classModel:id,name']),
            'message' => 'Fee structure created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $schoolId = $request->user()->school_id;
        $feeStructure = FeeStructure::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'class' => 'nullable|string',
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'fee_category_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_categories', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'category' => 'nullable|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'currency' => 'sometimes|string|in:USD,ZWG,ZWL',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('class_id') || $request->filled('class')) {
            [$classId, $className] = $this->resolveClass($request, $schoolId);
            $feeStructure->class_id = $classId;
            $feeStructure->class_name = $className;
        }

        if ($request->filled('fee_category_id') || $request->filled('category')) {
            [$categoryId, $categoryName] = $this->resolveCategory($request, $schoolId);
            $feeStructure->fee_category_id = $categoryId;
            $feeStructure->category = $categoryName;
        }

        if ($request->filled('amount')) {
            $feeStructure->amount = $request->amount;
        }
        if ($request->filled('currency')) {
            $feeStructure->currency = $request->currency === 'ZWL' ? 'ZWG' : $request->currency;
        }

        $feeStructure->save();

        return response()->json([
            'data' => $feeStructure->fresh()->load(['feeCategory:id,name,is_active', 'classModel:id,name']),
            'message' => 'Fee structure updated successfully',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $feeStructure = FeeStructure::findOrFail($id);
        $feeStructure->delete();

        return response()->json([
            'message' => 'Fee structure deleted successfully',
        ]);
    }

    /**
     * @return array{0: int|null, 1: string}
     */
    private function resolveClass(Request $request, ?int $schoolId): array
    {
        if ($request->filled('class_id')) {
            $class = ClassModel::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->findOrFail((int) $request->class_id);

            return [$class->id, $class->name];
        }

        $className = (string) $request->input('class');
        $class = ClassModel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('name', $className)
            ->first();

        return [$class?->id, $className];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function resolveCategory(Request $request, ?int $schoolId): array
    {
        if ($request->filled('fee_category_id')) {
            $category = FeeCategory::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->findOrFail((int) $request->fee_category_id);

            return [$category->id, $category->name];
        }

        $name = trim((string) $request->input('category'));
        $category = FeeCategory::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('name', $name)
            ->first();

        if (! $category) {
            $category = FeeCategory::create([
                'school_id' => $schoolId,
                'name' => $name,
                'description' => null,
                'is_active' => true,
                'order' => 0,
            ]);
        }

        return [$category->id, $category->name];
    }
}
