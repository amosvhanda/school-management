<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class GradeLevelController extends Controller
{
    private function authorizeAcademicManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['academics.manage'],
        );
    }

    /**
     * Get all grade levels for the school
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        
        $gradeLevels = GradeLevel::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'data' => $gradeLevels,
        ]);
    }

    /**
     * Store a new grade level
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAcademicManage($request);

        $schoolId = $request->user()->school_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('grade_levels')->where('school_id', $schoolId)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('grade_levels')->where('school_id', $schoolId)],
            'order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $gradeLevel = GradeLevel::create([
            'school_id' => $schoolId,
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Grade level created successfully',
            'data' => $gradeLevel,
        ], 201);
    }

    /**
     * Update a grade level
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAcademicManage($request);

        $schoolId = $request->user()->school_id;
        $gradeLevel = GradeLevel::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('grade_levels')->where('school_id', $schoolId)->ignore($id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('grade_levels')->where('school_id', $schoolId)->ignore($id)],
            'order' => ['sometimes', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $gradeLevel->update($validated);

        return response()->json([
            'message' => 'Grade level updated successfully',
            'data' => $gradeLevel,
        ]);
    }

    /**
     * Get a specific grade level
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $gradeLevel = GradeLevel::where('school_id', $schoolId)->findOrFail($id);

        return response()->json([
            'data' => $gradeLevel,
        ]);
    }

    /**
     * Delete a grade level
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAcademicManage($request);

        $schoolId = $request->user()->school_id;
        $gradeLevel = GradeLevel::where('school_id', $schoolId)->findOrFail($id);

        // Check if grade level is in use
        if ($gradeLevel->classes()->exists() || $gradeLevel->students()->exists()) {
            return response()->json([
                'message' => 'Cannot delete grade level that is in use'
            ], 422);
        }

        $gradeLevel->delete();

        return response()->json(['message' => 'Grade level deleted successfully']);
    }
}
