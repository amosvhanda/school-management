<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\CbtExamSession;
use App\Models\QuestionBankItem;
use App\Models\Subject;
use App\Services\Enterprise\ExaminationEnterpriseService;
use App\Services\FileUploadService;
use App\Services\StudentResolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StudentPortalController extends Controller
{
    public function __construct(
        private StudentResolutionService $students,
        private FileUploadService $uploads,
        private ExaminationEnterpriseService $cbt,
    ) {}

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

        $openAssignments = $this->attachSubmissions($openAssignments, $student->id);

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
                'open_assignments' => count($openAssignments),
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

        return $this->success($this->attachSubmissions($rows, $student->id));
    }

    public function showAssignment(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $assignment = $this->assignmentsForStudent($student, $schoolId)
            ->findOrFail($id);

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        return $this->success([
            'assignment' => $assignment,
            'submission' => $submission,
        ]);
    }

    public function submitAssignment(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $assignment = $this->assignmentsForStudent($student, $schoolId)
            ->findOrFail($id);

        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:20000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,jpg,jpeg,png,webp'],
        ]);

        if (empty($data['content']) && ! $request->hasFile('file')) {
            throw ValidationException::withMessages([
                'content' => ['Provide written work or attach a file before submitting.'],
            ]);
        }

        $existing = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && in_array($existing->status, ['submitted', 'graded'], true)) {
            throw ValidationException::withMessages([
                'assignment' => ['This assignment has already been submitted.'],
            ]);
        }

        $fileUrl = $existing?->file_url;
        if ($request->hasFile('file')) {
            $upload = $this->uploads->store(
                $request->file('file'),
                'assignments/submissions',
                'public',
                $schoolId,
            );
            $fileUrl = $upload['url'];
        }

        $submission = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'school_id' => $schoolId,
                'content' => $data['content'] ?? $existing?->content,
                'file_url' => $fileUrl,
                'status' => 'submitted',
                'submitted_at' => now(),
                'score' => null,
                'teacher_comment' => null,
                'graded_at' => null,
                'returned_at' => null,
            ],
        );

        return $this->created([
            'assignment' => $assignment->fresh(),
            'submission' => $submission->fresh(),
        ], 'Assignment submitted');
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
     * @param  Collection<int, Assignment>  $assignments
     * @return list<array<string, mixed>>
     */
    protected function attachSubmissions($assignments, int $studentId): array
    {
        if ($assignments->isEmpty()) {
            return [];
        }

        $submissions = AssignmentSubmission::query()
            ->where('student_id', $studentId)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        return $assignments->map(function (Assignment $assignment) use ($submissions) {
            $submission = $submissions->get($assignment->id);

            return array_merge($assignment->toArray(), [
                'submission_status' => $submission?->status,
                'submission_score' => $submission?->score,
                'submitted_at' => $submission?->submitted_at?->toIso8601String(),
                'teacher_comment' => $submission?->teacher_comment,
                'submission_file_url' => $submission?->file_url,
            ]);
        })->values()->all();
    }

    public function cbtAvailable(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $subjects = QuestionBankItem::query()
            ->where('school_id', $schoolId)
            ->selectRaw('subject_id, COUNT(*) as question_count')
            ->whereNotNull('subject_id')
            ->groupBy('subject_id')
            ->havingRaw('COUNT(*) >= 1')
            ->get();

        $names = Subject::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $subjects->pluck('subject_id'))
            ->pluck('name', 'id');

        $active = CbtExamSession::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        return $this->success([
            'subjects' => $subjects->map(fn ($row) => [
                'subject_id' => (int) $row->subject_id,
                'subject_name' => $names[(int) $row->subject_id] ?? 'Subject '.$row->subject_id,
                'question_count' => (int) $row->question_count,
            ])->values()->all(),
            'active_session' => $active ? [
                'id' => $active->id,
                'exam_id' => $active->exam_id,
                'started_at' => $active->started_at?->toIso8601String(),
                'question_count' => is_array($active->question_ids) ? count($active->question_ids) : 0,
            ] : null,
        ]);
    }

    public function startCbt(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'count' => ['nullable', 'integer', 'min:1', 'max:40'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);

        $existing = CbtExamSession::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($existing) {
            return $this->success($this->cbt->sessionPayloadForStudent($existing), 'Resuming in-progress session');
        }

        $count = (int) ($data['count'] ?? 10);
        $questions = $this->cbt->generateRandomPaper($schoolId, (int) $data['subject_id'], $count);
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'subject_id' => ['No questions are available for this subject yet.'],
            ]);
        }

        $session = $this->cbt->startCbtSession(
            $schoolId,
            $student->id,
            $questions->pluck('id')->all(),
            $data['exam_id'] ?? null,
        );

        return $this->created($this->cbt->sessionPayloadForStudent($session), 'CBT session started');
    }

    public function showCbtSession(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $session = CbtExamSession::query()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id)
            ->findOrFail($id);

        return $this->success($this->cbt->sessionPayloadForStudent($session));
    }

    public function submitCbt(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $session = CbtExamSession::query()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id)
            ->findOrFail($id);

        if ($session->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'session' => ['This exam session is already submitted.'],
            ]);
        }

        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer' => ['required', 'string'],
        ]);

        $submitted = $this->cbt->submitCbtSession($session, $data['answers']);

        return $this->success([
            'id' => $submitted->id,
            'status' => $submitted->status,
            'score' => $submitted->score,
            'submitted_at' => $submitted->submitted_at?->toIso8601String(),
        ], 'Exam submitted');
    }

    public function cbtAntiCheat(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $session = CbtExamSession::query()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id)
            ->findOrFail($id);

        $data = $request->validate([
            'event_type' => ['required', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $this->cbt->logAntiCheat($session->id, $data['event_type'], $data['metadata'] ?? null);

        return $this->success(message: 'Anti-cheat event logged');
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
