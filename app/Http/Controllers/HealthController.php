<?php

namespace App\Http\Controllers;

use App\Models\ClinicVisit;
use App\Models\Student;
use App\Models\StudentMedicalProfile;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function profile(Request $request, int $studentId)
    {
        $schoolId = $request->user()->school_id;

        // Ensure student actually belongs to this school context
        Student::where('school_id', $schoolId)->findOrFail($studentId);

        // Keep tenant context explicitly applied when instantiating raw profiles
        $profile = StudentMedicalProfile::firstOrCreate(
            ['student_id' => $studentId],
            ['school_id' => $schoolId] // Highly recommended schema addition
        );

        return response()->json(['data' => $profile]);
    }

    public function updateProfile(Request $request, int $studentId)
    {
        $schoolId = $request->user()->school_id;
        Student::where('school_id', $schoolId)->findOrFail($studentId);

        $data = $request->validate([
            'conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'blood_group' => 'nullable|string|max:10',
            'emergency_notes' => 'nullable|string',
            'medications' => 'nullable|string',
        ]);

        $profile = StudentMedicalProfile::updateOrCreate(
            ['student_id' => $studentId],
            [...$data, 'school_id' => $schoolId]
        );

        return response()->json(['data' => $profile]);
    }

    public function visits(Request $request)
    {
        $visits = ClinicVisit::where('school_id', $request->user()->school_id)
            ->with('student:id,full_name,student_number')
            ->orderByDesc('visit_date')
            ->limit(100)
            ->get();

        return response()->json(['data' => $visits]);
    }

    public function recordVisit(Request $request)
    {
        $schoolId = $request->user()->school_id;

        $data = $request->validate([
            'student_id' => 'required|integer', // Changed from unsafe cross-tenant exists rule
            'visit_date' => 'required|date',
            'complaint' => 'required|string',
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
        ]);

        // Explicitly seal tenant boundary check
        Student::where('school_id', $schoolId)->findOrFail($data['student_id']);

        $visit = ClinicVisit::create([
            ...$data,
            'school_id' => $schoolId,
            'nurse_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $visit], 201);
    }

    public function updateVisit(Request $request, int $id)
    {
        $schoolId = $request->user()->school_id;
        $visit = ClinicVisit::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'visit_date' => 'sometimes|date',
            'complaint' => 'sometimes|string',
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
        ]);

        $visit->update($data);

        return response()->json(['data' => $visit->fresh()->load('student:id,full_name,student_number'), 'message' => 'Visit updated']);
    }
}
