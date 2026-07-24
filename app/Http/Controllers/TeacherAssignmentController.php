<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\PermissionService;
use App\Services\TeacherResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentController extends Controller
{
    public function __construct(
        private TeacherResolutionService $teacherResolution,
    ) {}

    /**
     * Get teacher assignments for the school.
     * Admins manage all; teachers may only list their own assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $user->school_id;
        $service = app(PermissionService::class);
        $canManage = $service->hasCapability($user, 'canManageTeachers')
            || $service->hasPermission($user, 'academics.manage')
            || $service->hasPermission($user, 'hr.manage');

        $ownTeacherId = $this->teacherResolution->resolveForUser($user)?->id;
        $requestedTeacherId = $request->filled('teacher_id') ? (int) $request->teacher_id : null;

        if (! $canManage) {
            abort_unless($ownTeacherId, 403, 'Teacher profile not linked to this account.');
            abort_unless(
                $requestedTeacherId === null || $requestedTeacherId === (int) $ownTeacherId,
                403,
                'You can only view your own teaching assignments.'
            );
            $requestedTeacherId = (int) $ownTeacherId;
        }

        $query = TeacherAssignment::where('school_id', $schoolId)
            ->with(['teacher', 'gradeLevel', 'classModel', 'subject']);

        if ($requestedTeacherId) {
            $query->where('teacher_id', $requestedTeacherId);
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

        $this->assertSchoolScopedRelations($schoolId, $validated);
        $this->assertMeaningfulAssignment($validated);

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

        $this->syncHomeroomIfNeeded($assignment);

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

        $candidate = array_merge($assignment->only([
            'teacher_id', 'class_id', 'subject_id', 'grade_level_id', 'role', 'is_active',
        ]), $validated);

        $this->assertSchoolScopedRelations($schoolId, $candidate);
        $this->assertMeaningfulAssignment($candidate);

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
        $this->syncHomeroomIfNeeded($assignment->fresh());

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
    private function assertSchoolScopedRelations(int $schoolId, array $data): void
    {
        if (! empty($data['teacher_id'])) {
            Teacher::where('school_id', $schoolId)->findOrFail($data['teacher_id']);
        }

        if (! empty($data['grade_level_id'])) {
            GradeLevel::where('school_id', $schoolId)->findOrFail($data['grade_level_id']);
        }

        if (! empty($data['class_id'])) {
            ClassModel::where('school_id', $schoolId)->findOrFail($data['class_id']);
        }

        if (! empty($data['subject_id'])) {
            Subject::where('school_id', $schoolId)->findOrFail($data['subject_id']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertMeaningfulAssignment(array $data): void
    {
        $hasClass = ! empty($data['class_id']);
        $hasSubject = ! empty($data['subject_id']);
        $hasGrade = ! empty($data['grade_level_id']);

        if ($hasClass || $hasSubject || $hasGrade) {
            return;
        }

        throw ValidationException::withMessages([
            'class_id' => ['Assign a class, subject, or grade level so the relationship is usable.'],
        ]);
    }

    private function syncHomeroomIfNeeded(?TeacherAssignment $assignment): void
    {
        if (! $assignment || ! $assignment->class_id || ! $assignment->is_active) {
            return;
        }

        $role = strtolower((string) ($assignment->role ?? ''));
        if (! in_array($role, ['class_teacher', 'homeroom', 'form_teacher'], true)) {
            return;
        }

        // Only set when the class has no homeroom teacher yet.
        ClassModel::query()
            ->where('id', $assignment->class_id)
            ->where('school_id', $assignment->school_id)
            ->whereNull('teacher_id')
            ->update(['teacher_id' => $assignment->teacher_id]);
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
