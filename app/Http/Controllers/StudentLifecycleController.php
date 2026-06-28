<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\DisciplinaryRecord;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\HostelAllocation;
use App\Models\StudentMedicalProfile;
use App\Models\StudentTransportAllocation;
use App\Models\ExamResult;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Http\Request;

class StudentLifecycleController extends Controller
{
    public function show(Request $request, int $student)
    {
        $schoolId = $request->user()?->school_id;

        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['classModel', 'gradeLevel', 'school', 'guardians', 'parents:id,name,email,phone'])
            ->findOrFail($student);

        $enrollments = Enrollment::query()
            ->where('student_id', $student->id)
            ->with('classModel:id,name')
            ->orderByDesc('enrolled_at')
            ->get();

        $applications = EnrollmentApplication::query()
            ->where('school_id', $student->school_id)
            ->where(function ($q) use ($student) {
                $q->where('student_id', $student->id)
                    ->orWhere('email', $student->email)
                    ->orWhere('phone', $student->phone);
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $attendanceSummary = Attendance::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentAttendance = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(20)
            ->get(['id', 'date', 'status', 'class_id', 'subject_id', 'remarks']);

        $examHistory = ExamResult::query()
            ->where('student_id', $student->id)
            ->with(['exam:id,name,exam_date,academic_year', 'subject:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $finance = [
            'balance' => (float) $student->balance,
            'currency' => $student->currency,
            'invoices' => Invoice::where('student_id', $student->id)->orderByDesc('created_at')->limit(20)->get(),
            'payments' => Payment::where('student_id', $student->id)->orderByDesc('date')->limit(20)->get(),
            'transactions' => Transaction::where('student_id', $student->id)->orderByDesc('created_at')->limit(30)->get(),
        ];

        $discipline = DisciplinaryRecord::query()
            ->where('student_id', $student->id)
            ->orderByDesc('incident_date')
            ->get();

        $transport = StudentTransportAllocation::query()
            ->where('student_id', $student->id)
            ->with('route.vehicle', 'route.driver')
            ->get();

        $hostel = HostelAllocation::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->with(['bed.room.hostel'])
            ->first();

        $medical = StudentMedicalProfile::where('student_id', $student->id)->first();

        return response()->json([
            'data' => [
                'student' => $student,
                'admission' => [
                    'applications' => $applications,
                    'enrollments' => $enrollments,
                    'status' => $student->status,
                    'repetition_count' => $student->repetition_count,
                    'previous_school' => $student->previous_school,
                ],
                'academics' => [
                    'grades' => $student->grades()->orderByDesc('year')->limit(50)->get(),
                    'exam_results' => $examHistory,
                ],
                'attendance' => [
                    'summary' => $attendanceSummary,
                    'recent' => $recentAttendance,
                ],
                'finance' => $finance,
                'discipline' => $discipline,
                'medical' => $medical,
                'transport' => $transport,
                'hostel' => $hostel,
                'guardians' => $student->guardians,
                'parents' => $student->parents,
            ],
        ]);
    }
}
