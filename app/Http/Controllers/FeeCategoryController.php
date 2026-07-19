<?php

namespace App\Http\Controllers;

use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeeCategoryController extends Controller
{
    /**
     * Get all fee categories for the school
     */
    public function index(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $user = $request->user();
        $schoolId = $user->school_id;

        $query = FeeCategory::where('school_id', $schoolId)
            ->withCount('feeStructures');

        // Support 'all=true' parameter
        if ($request->boolean('all') && ! $request->has('is_active')) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('order')->orderBy('name')->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Get a specific fee category
     */
    public function show(Request $request, $id)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $user = $request->user();
        $schoolId = $user->school_id;

        $category = FeeCategory::where('school_id', $schoolId)
            ->withCount('feeStructures')
            ->with(['feeStructures' => fn ($q) => $q->with('classModel:id,name')->orderBy('class_name')])
            ->findOrFail($id);

        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Create a new fee category
     */
    public function store(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fee_categories')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                }),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = FeeCategory::create([
            'school_id' => $schoolId,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'message' => 'Fee category created successfully',
            'data' => $category->loadCount('feeStructures'),
        ], 201);
    }

    /**
     * Update a fee category
     */
    public function update(Request $request, $id)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $user = $request->user();
        $schoolId = $user->school_id;

        $category = FeeCategory::where('school_id', $schoolId)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('fee_categories')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })->ignore($id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldName = $category->name;

        DB::transaction(function () use ($request, $category, $oldName) {
            $category->update($request->only([
                'name',
                'description',
                'is_active',
                'order',
            ]));

            // Keep denormalized category string on fee structures in sync.
            if ($request->filled('name') && $request->name !== $oldName) {
                $category->feeStructures()->update(['category' => $category->name]);
            }
        });

        return response()->json([
            'message' => 'Fee category updated successfully',
            'data' => $category->fresh()->loadCount('feeStructures'),
        ]);
    }

    /**
     * Delete a fee category
     */
    public function destroy(Request $request, $id)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $user = $request->user();
        $schoolId = $user->school_id;

        $category = FeeCategory::where('school_id', $schoolId)
            ->findOrFail($id);

        if ($category->feeStructures()->exists()) {
            return response()->json([
                'message' => 'Cannot delete fee category that is in use by fee structures. Reassign or delete those structures first.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Fee category deleted successfully',
        ]);
    }
}
