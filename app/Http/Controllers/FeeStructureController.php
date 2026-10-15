<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPaginatedList;
use App\Http\Requests\Api\V1\Finance\BulkAssignFeeStructureRequest;
use App\Http\Requests\Api\V1\Finance\StoreFeeStructureRequest;
use App\Http\Requests\Api\V1\Finance\UpdateFeeStructureRequest;
use App\Models\ClassModel;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use Illuminate\Http\Request;

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

    public function store(StoreFeeStructureRequest $request)
    {
        $schoolId = $request->user()->school_id;

        [$classId, $className] = $this->resolveClass($request, $schoolId);
        [$categoryId, $categoryName] = $this->resolveCategory($request, $schoolId);

        $schoolCurrency = $request->user()->school?->getDefaultCurrency() ?? 'USD';
        $currency = $request->filled('currency')
            ? ($request->input('currency') === 'ZWL' ? 'ZWG' : $request->input('currency'))
            : $schoolCurrency;

        $feeStructure = FeeStructure::create([
            'class_id' => $classId,
            'class_name' => $className,
            'fee_category_id' => $categoryId,
            'category' => $categoryName,
            'amount' => $request->validated('amount'),
            'currency' => $currency,
            'school_id' => $schoolId,
        ]);

        return response()->json([
            'data' => $feeStructure->load(['feeCategory:id,name,is_active', 'classModel:id,name']),
            'message' => 'Fee structure created successfully',
        ], 201);
    }

    public function update(UpdateFeeStructureRequest $request, $id)
    {
        $schoolId = $request->user()->school_id;
        $feeStructure = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        $data = $request->validated();

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

        if (array_key_exists('amount', $data)) {
            $feeStructure->amount = $data['amount'];
        }
        if (array_key_exists('currency', $data)) {
            $feeStructure->currency = $data['currency'] === 'ZWL' ? 'ZWG' : $data['currency'];
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

        $feeStructure = FeeStructure::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($id);
        $feeStructure->delete();

        return response()->json([
            'message' => 'Fee structure deleted successfully',
        ]);
    }

    public function bulkAssign(BulkAssignFeeStructureRequest $request)
    {
        $schoolId = $request->user()?->school_id;
        if (! $schoolId) {
            abort(403, 'User is not associated with a school.');
        }

        $data = $request->validated();

        $source = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->findOrFail((int) $data['fee_structure_id']);

        $classIds = collect($data['class_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $classes = ClassModel::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $classIds->all())
            ->get()
            ->keyBy('id');

        $assigned = [];

        foreach ($classIds as $classId) {
            $class = $classes->get($classId);
            if (! $class) {
                continue;
            }

            $existing = FeeStructure::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $class->id)
                ->where(function ($q) use ($source) {
                    if ($source->fee_category_id) {
                        $q->where('fee_category_id', $source->fee_category_id);
                    } else {
                        $q->where('category', $source->category);
                    }
                })
                ->first();

            $payload = [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'fee_category_id' => $source->fee_category_id,
                'category' => $source->category,
                'amount' => $source->amount,
                'currency' => $source->currency,
                'school_id' => $schoolId,
            ];

            if ($existing) {
                $existing->update($payload);
                $assigned[] = $existing->fresh()->load(['feeCategory:id,name,is_active', 'classModel:id,name']);
            } else {
                $assigned[] = FeeStructure::create($payload)
                    ->load(['feeCategory:id,name,is_active', 'classModel:id,name']);
            }
        }

        return response()->json([
            'data' => $assigned,
            'message' => 'Fee structure assigned to selected classes',
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
