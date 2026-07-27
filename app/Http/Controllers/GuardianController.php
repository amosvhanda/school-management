<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\Guardian\LinkGuardianStudentRequest;
use App\Http\Requests\Api\V1\Guardian\StoreGuardianRequest;
use App\Http\Requests\Api\V1\Guardian\UpdateGuardianRequest;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\GuardianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardianController extends Controller
{
    public function __construct(protected GuardianService $guardianService) {}

    /**
     * Get all guardians for the school
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;

        $guardians = Guardian::where('school_id', $schoolId)
            ->with(['students', 'user'])
            ->get();

        return response()->json($guardians);
    }

    /**
     * Get a specific guardian
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $guardian = Guardian::where('school_id', $schoolId)
            ->with(['students', 'user'])
            ->findOrFail($id);

        return response()->json($guardian);
    }

    /**
     * Get students for a guardian
     */
    public function students(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;

        // Enforce boundary scope check on the parent entity first
        Guardian::where('school_id', $schoolId)->findOrFail($id);

        $students = $this->guardianService->getGuardianStudents($id);

        return response()->json($students);
    }

    /**
     * Get guardians for a student
     */
    public function forStudent(Request $request, int $student): JsonResponse
    {
        $schoolId = $request->user()->school_id;

        // Enforce boundary scope check on the child entity first
        Student::where('school_id', $schoolId)->findOrFail($student);

        $guardians = $this->guardianService->getStudentGuardians($student);

        return response()->json($guardians);
    }

    /**
     * Store a new guardian
     */
    public function store(StoreGuardianRequest $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $validated = $request->validated();
        $studentId = isset($validated['student_id']) ? (int) $validated['student_id'] : null;

        // Secure incoming student relationship boundary if passed
        if ($studentId) {
            Student::where('school_id', $schoolId)->findOrFail($studentId);
        }

        $guardian = $this->guardianService->createOrFindGuardian(
            $validated,
            $schoolId,
            $studentId
        );

        return response()->json($guardian->load(['students', 'user']), 201);
    }

    /**
     * Update a guardian
     */
    public function update(UpdateGuardianRequest $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $guardian = Guardian::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validated();
        $studentId = isset($validated['student_id']) ? (int) $validated['student_id'] : null;
        unset($validated['student_id']);

        // Explicitly isolate cross-tenant updates inside a database transaction block
        DB::transaction(function () use ($guardian, $validated, $studentId, $schoolId) {
            $guardian->update($validated);

            if ($studentId) {
                // Seal pivot injection loophole by validating student scope explicitly
                Student::where('school_id', $schoolId)->findOrFail($studentId);

                $pivot = [
                    'relationship' => $validated['relationship'] ?? $guardian->relationship ?? 'parent',
                    'is_primary' => $validated['is_primary'] ?? true,
                    'can_pickup' => true,
                    'emergency_contact' => false,
                ];

                $guardian->students()->syncWithoutDetaching([$studentId => $pivot]);
                $this->guardianService->syncParentUserLink($guardian, $studentId, $pivot);
            }
        });

        return response()->json($guardian->load(['students', 'user']));
    }

    /**
     * Link guardian to student
     */
    public function linkToStudent(LinkGuardianStudentRequest $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $guardian = Guardian::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validated();
        $studentId = (int) $validated['student_id'];

        // Secure the relationship verification: prevent multi-tenant link injection
        Student::where('school_id', $schoolId)->findOrFail($studentId);

        DB::transaction(function () use ($guardian, $studentId, $validated) {
            $guardian->students()->syncWithoutDetaching([
                $studentId => [
                    'relationship' => $validated['relationship'] ?? 'parent',
                    'is_primary' => $validated['is_primary'] ?? false,
                    'can_pickup' => $validated['can_pickup'] ?? true,
                    'emergency_contact' => $validated['emergency_contact'] ?? false,
                ]
            ]);

            $this->guardianService->syncParentUserLink($guardian, $studentId, $validated);
        });

        return response()->json($guardian->load(['students']));
    }

    /**
     * Delete a guardian (blocked while students are linked).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $guardian = Guardian::where('school_id', $schoolId)
            ->withCount('students')
            ->findOrFail($id);

        if ($guardian->students_count > 0) {
            return response()->json([
                'message' => 'Unlink all students before deleting this guardian.',
            ], 422);
        }

        $guardian->delete();

        return response()->json(['message' => 'Guardian deleted']);
    }
}
