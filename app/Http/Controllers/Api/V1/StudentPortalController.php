<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Timetable;
use App\Services\StudentResolutionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StudentPortalController extends Controller
{
    public function __construct(private StudentResolutionService $students) {}

    public function dashboard(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;
        $today = now()->toDateString();

        $recentAttendance = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'date', 'status', 'remarks']);

        $upcomingExams = Exam::query()
            ->where('school_id', $schoolId)
            ->whereDate('exam_date', '>=', $today)
            ->when($student->grade_level_id, fn ($q) => $q->where(function ($inner) use ($student) {
                $inner->whereNull('grade_level_id')->orWhere('grade_level_id', $student->grade_level_id);
            }))
            ->orderBy('exam_date')
            ->limit(8)
            ->get(['id', 'name', 'exam_date', 'is_published']);

        $openInvoices = Invoice::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderByDesc('due_date')
            ->limit(8)
            ->get(['id', 'invoice_number', 'amount', 'balance', 'status', 'due_date', 'currency']);

        $recentGrades = Grade::query()
            ->where('student_id', $student->id)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'subject', 'subject_id', 'assessment_type', 'score', 'total', 'grade', 'term', 'year', 'updated_at']);

        $openAssignments = $this->assignmentsForStudent($student, $schoolId)
            ->limit(8)
            ->get(['id', 'title', 'subject', 'due_date', 'total_marks', 'status']);

        $announcements = Announcement::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('target_audience', 'all')
                    ->orWhere('target_audience', 'students');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'title', 'message', 'type', 'date', 'created_at']);

        $attendanceStats = $this->attendanceStats($student->id);

        return $this->success([
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'student_number' => $student->student_number,
                'class_id' => $student->class_id,
                'class_name' => $student->classModel?->name ?? $student->class,
                'status' => $student->status,
                'balance' => (float) $student->balance,
                'currency' => $student->currency,
            ],
            'attendance_recent' => $recentAttendance,
            'attendance_summary' => $attendanceStats,
            'upcoming_exams' => $upcomingExams,
            'open_invoices' => $openInvoices,
            'recent_grades' => $recentGrades,
            'assignments' => $openAssignments,
            'announcements' => $announcements,
            'stats' => [
                'balance' => (float) $student->balance,
                'open_invoices' => $openInvoices->count(),
                'upcoming_exams' => $upcomingExams->count(),
                'open_assignments' => $openAssignments->count(),
                'absent_last_30' => Attendance::query()
                    ->where('student_id', $student->id)
                    ->where('status', 'absent')
                    ->whereDate('date', '>=', now()->subDays(30))
                    ->count(),
                'attendance_rate' => $attendanceStats['attendance_rate'],
            ],
        ]);
    }

    public function me(Request $request)
    {
        $student = $this->requireStudent($request);
        $student->loadMissing(['classModel:id,name', 'gradeLevel:id,name']);

        return $this->success($student);
    }

    public function attendance(Request $request)
    {
        $student = $this->requireStudent($request);

        $rows = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(90)
            ->get(['id', 'date', 'status', 'remarks', 'time_in']);

        return $this->success([
            'summary' => $this->attendanceStats($student->id),
            'rows' => $rows,
        ]);
    }

    public function grades(Request $request)
    {
        $student = $this->requireStudent($request);

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->orderByDesc('year')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $examResults = ExamResult::query()
            ->where('student_id', $student->id)
            ->with('exam:id,name,exam_date,status')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return $this->success([
            'grades' => $grades,
            'exam_results' => $examResults,
        ]);
    }

    public function fees(Request $request)
    {
        $student = $this->requireStudent($request);

        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->orderByDesc('due_date')
            ->limit(50)
            ->get();

        return $this->success([
            'balance' => (float) $student->balance,
            'currency' => $student->currency,
            'invoices' => $invoices,
        ]);
    }

    public function timetable(Request $request)
    {
        $student = $this->requireStudent($request);

        if (! $student->class_id) {
            return $this->success([]);
        }

        $slots = Timetable::query()
            ->where('school_id', $request->user()->school_id)
            ->where('class_id', $student->class_id)
            ->with(['teacher:id,name,first_name,last_name', 'classModel:id,name'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->limit(100)
            ->get();

        return $this->success($slots);
    }

    public function assignments(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $rows = $this->assignmentsForStudent($student, $schoolId)
            ->limit(100)
            ->get();

        return $this->success($rows);
    }

    public function announcements(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $rows = Announcement::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('target_audience', 'all')
                    ->orWhere('target_audience', 'students');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'title', 'message', 'type', 'date', 'created_at']);

        return $this->success($rows);
    }

    protected function assignmentsForStudent(Student $student, int $schoolId)
    {
        return Assignment::query()
            ->where('school_id', $schoolId)
            ->when($student->class_id, fn ($q) => $q->where(function ($inner) use ($student) {
                $inner->whereNull('class_id')->orWhere('class_id', $student->class_id);
            }))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['closed', 'archived', 'cancelled']);
            })
            ->orderBy('due_date')
            ->orderByDesc('id');
    }

    /**
     * @return array{present:int,late:int,absent:int,excused:int,total_days:int,attendance_rate:float}
     */
    protected function attendanceStats(int $studentId): array
    {
        $rows = Attendance::query()
            ->where('student_id', $studentId)
            ->whereDate('date', '>=', now()->subDays(90))
            ->get(['status']);

        $present = $rows->where('status', 'present')->count();
        $late = $rows->where('status', 'late')->count();
        $absent = $rows->where('status', 'absent')->count();
        $excused = $rows->where('status', 'excused')->count();
        $total = $rows->count();

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'excused' => $excused,
            'total_days' => $total,
            'attendance_rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0.0,
        ];
    }

    protected function requireStudent(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->role !== UserRole::Student) {
            throw new AccessDeniedHttpException('Student portal access is restricted to student accounts.');
        }

        $student = $this->students->resolveForUser($user);
        if (! $student) {
            throw new AccessDeniedHttpException('Student profile is not linked to this account.');
        }

        $student->loadMissing('classModel:id,name');

        return $student;
    }
}
