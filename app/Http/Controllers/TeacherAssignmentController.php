<?php

namespace App\Http\Controllers;

use App\Models\TeacherAssignment;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherAssignmentController extends Controller
{
    /**
     * Get all teacher assignments for the school
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['academics.manage', 'hr.manage'],
        );

        $schoolId = $request->user()->school_id;
        
        $query = TeacherAssignment::where('school_id', $schoolId)
            ->with(['teacher', 'gradeLevel', 'classModel', 'subject']);

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        $assignments = $query->get();

        return response()->json($assignments);
    }

    /**
     * Store a new teacher assignment
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['academics.manage', 'hr.manage'],
        );

        $schoolId = $request->user()->school_id;

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'grade_level_id' => ['nullable', 'exists:grade_levels,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'role' => ['nullable', 'string', 'max:100'],
            'assigned_at' => ['nullable', 'date'],
            'assigned_until' => ['nullable', 'date', 'after:assigned_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Validate teacher belongs to school
        $teacher = Teacher::where('school_id', $schoolId)
            ->findOrFail($validated['teacher_id']);

        // Validate grade level belongs to school
        if (!empty($validated['grade_level_id'])) {
            \App\Models\GradeLevel::where('school_id', $schoolId)
                ->findOrFail($validated['grade_level_id']);
        }

        // Validate class belongs to school
        if (!empty($validated['class_id'])) {
            \App\Models\ClassModel::where('school_id', $schoolId)
                ->findOrFail($validated['class_id']);
        }

        if ($this->activeAssignmentExists($schoolId, $validated)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'teacher_id' => ['This teacher is already assigned to that class and subject.'],
                ],
            ], 422);
        }

        $assignment = TeacherAssignment::create([
            'school_id' => $schoolId,
            ...$validated,
            'assigned_at' => $validated['assigned_at'] ?? now(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($assignment->load(['teacher', 'gradeLevel', 'classModel', 'subject']), 201);
    }

    /**
     * Update a teacher assignment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['academics.manage', 'hr.manage'],
        );

        $schoolId = $request->user()->school_id;
        $assignment = TeacherAssignment::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => ['sometimes', 'exists:teachers,id'],
            'grade_level_id' => ['nullable', 'exists:grade_levels,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'role' => ['nullable', 'string', 'max:100'],
            'assigned_at' => ['nullable', 'date'],
            'assigned_until' => ['nullable', 'date', 'after:assigned_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Validate teacher belongs to school if provided
        if (!empty($validated['teacher_id'])) {
            Teacher::where('school_id', $schoolId)
                ->findOrFail($validated['teacher_id']);
        }

        $candidate = array_merge($assignment->only([
            'teacher_id', 'class_id', 'subject_id', 'is_active',
        ]), $validated);

        if (($candidate['is_active'] ?? true)
            && $this->activeAssignmentExists($schoolId, $candidate, excludeId: $assignment->id)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'teacher_id' => ['This teacher is already assigned to that class and subject.'],
                ],
            ], 422);
        }

        $assignment->update($validated);

        return response()->json($assignment->load(['teacher', 'gradeLevel', 'classModel', 'subject']));
    }

    /**
     * Delete a teacher assignment
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['academics.manage', 'hr.manage'],
        );

        $schoolId = $request->user()->school_id;
        $assignment = TeacherAssignment::where('school_id', $schoolId)->findOrFail($id);

        $assignment->delete();

        return response()->json(['message' => 'Teacher assignment deleted successfully']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function activeAssignmentExists(int $schoolId, array $data, ?int $excludeId = null): bool
    {
        $query = TeacherAssignment::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $data['teacher_id'])
            ->where('is_active', true);

        if (array_key_exists('class_id', $data) && $data['class_id'] !== null) {
            $query->where('class_id', $data['class_id']);
        } else {
            $query->whereNull('class_id');
        }

        if (array_key_exists('subject_id', $data) && $data['subject_id'] !== null) {
            $query->where('subject_id', $data['subject_id']);
        } else {
            $query->whereNull('subject_id');
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
