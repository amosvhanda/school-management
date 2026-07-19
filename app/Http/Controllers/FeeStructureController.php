<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $query = FeeStructure::query();

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('currency')) {
            $query->where('currency', $request->currency);
        }

        // Support 'all=true' parameter to get all fee structures without pagination
        if ($request->get('all') === 'true' || $request->get('all') === true) {
            $feeStructures = $query->orderBy('class_name')->orderBy('category')->get();
        } else {
            $limit = $request->get('limit', 50);
            $feeStructures = $query->orderBy('class_name')->orderBy('category')->limit($limit)->get();
        }

        return response()->json([
            'data' => $feeStructures,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $validator = Validator::make($request->all(), [
            'class' => 'required|string',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:USD,ZWG',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feeStructure = FeeStructure::create([
            'class_name' => $request->class,
            'category' => $request->category,
            'amount' => $request->amount,
            'currency' => $request->currency,
        ]);

        return response()->json([
            'data' => $feeStructure,
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

        $feeStructure = FeeStructure::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0.01',
            'currency' => 'sometimes|string|in:USD,ZWG',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feeStructure->fill($request->only(['amount', 'currency']));
        $feeStructure->save();

        return response()->json([
            'data' => $feeStructure,
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
}
