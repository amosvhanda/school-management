<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use App\Services\EnrollmentApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    public function __construct(private EnrollmentApprovalService $approvalService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $query = EnrollmentApplication::query()->orderByDesc('created_at');

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function show($id)
    {
        $user = request()->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $query = EnrollmentApplication::query()->where('id', $id);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        $application = $query->firstOrFail();

        return response()->json(['data' => $application->load('student')]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:male,female',
            'phone' => 'required|string',
            'address' => 'required|string',
            'grade_applying_for' => 'required|string',
            'academic_year' => 'required|string',
            'guardian_first_name' => 'required|string|max:255',
            'guardian_surname' => 'required|string|max:255',
            'guardian_phone' => 'required|string',
            'guardian_relationship' => 'required|string',
            'guardian_address' => 'required|string',
            'emergency_contact' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        if ($user->school_id === null) {
            return response()->json(['message' => 'User must belong to a school to create enrollment applications.'], 403);
        }

        $application = EnrollmentApplication::create([
            ...$request->only([
                'first_name', 'surname', 'date_of_birth', 'gender', 'national_id', 'address', 'suburb',
                'phone', 'email', 'previous_school', 'grade_applying_for', 'academic_year',
                'guardian_first_name', 'guardian_surname', 'guardian_relationship', 'guardian_phone',
                'guardian_email', 'guardian_address', 'guardian_employer', 'medical_conditions',
                'allergies', 'emergency_contact', 'emergency_phone',
            ]),
            'school_id' => $user->school_id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Enrollment application created successfully',
            'data' => $application,
        ], 201);
    }

    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $query = EnrollmentApplication::query()->where('id', $id);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        $application = $query->firstOrFail();

        if ($application->status === 'rejected') {
            return response()->json(['message' => 'Cannot approve a rejected application.'], 422);
        }

        $student = $this->approvalService->approve(
            $application,
            $request->input('class_id'),
            $user->id,
        );

        return response()->json([
            'message' => 'Enrollment approved. Student admitted with ID and fee ledger created.',
            'data' => [
                'application' => $application->fresh(),
                'student' => $student,
            ],
        ]);
    }

    public function reject(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $query = EnrollmentApplication::query()->where('id', $id);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        $application = $query->firstOrFail();

        $application->update([
            'status' => 'rejected',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'notes' => $request->input('notes', ''),
        ]);

        return response()->json(['message' => 'Enrollment application rejected']);
    }
}
