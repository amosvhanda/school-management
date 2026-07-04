<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Get all departments for the school
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $query = Department::where('school_id', $schoolId)
            ->with('headTeacher:id,name,employee_id');

        // Support 'all=true' parameter
        if ($request->get('all') === 'true' || $request->get('all') === true) {
            if (!$request->has('is_active')) {
                $query->where('is_active', true);
            }
        }

        // Filter by search if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by is_active if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $departments = $query->orderBy('name')->get();

        return response()->json([
            'data' => $departments,
        ]);
    }

    /**
     * Get a specific department
     */
    public function show(Request $request, Department $department)
    {
        $department->load('headTeacher:id,name,employee_id');

        return response()->json([
            'data' => $department,
        ]);
    }

    /**
     * Create a new department
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('departments')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'description' => 'nullable|string',
            'head_teacher_id' => 'nullable|exists:teachers,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $department = Department::create([
            'school_id' => $schoolId,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'head_teacher_id' => $request->head_teacher_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $department->load('headTeacher:id,name,employee_id');

        return response()->json([
            'message' => 'Department created successfully',
            'data' => $department,
        ], 201);
    }

    /**
     * Update a department
     */
    public function update(Request $request, Department $department)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('departments')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })->ignore($department->id)
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('departments')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })->ignore($department->id)
            ],
            'description' => 'nullable|string',
            'head_teacher_id' => 'nullable|exists:teachers,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $department->update($request->only([
            'name',
            'code',
            'description',
            'head_teacher_id',
            'is_active',
        ]));

        $department->load('headTeacher:id,name,employee_id');

        return response()->json([
            'message' => 'Department updated successfully',
            'data' => $department->fresh(),
        ]);
    }

    /**
     * Delete a department
     */
    public function destroy(Request $request, Department $department)
    {
        // Check if department has teachers
        if ($department->teachers()->exists()) {
            return response()->json([
                'message' => 'Cannot delete department that has teachers assigned',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully',
        ]);
    }
}
