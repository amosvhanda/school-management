<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\TeachingAssistant;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\BehaviorPoint;
use App\Models\ClassModel;
use App\Models\ClassParticipationRecord;
use App\Models\ClassSubstitution;
use App\Models\DisciplinaryRecord;
use App\Models\Exam;
use App\Models\LeaveRequest;
use App\Models\LessonPlan;
use App\Models\OnlineLesson;
use App\Models\ReportCardNarrative;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\StudentIntervention;
use App\Models\SyllabusTopic;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherNotification;
use App\Models\TeachingResource;
use App\Models\Timetable;
use App\Models\TimetableChangeRequest;
use App\Services\Export\ExportService;
use App\Services\Domain\SchoolDomainRules;
use App\Services\PermissionService;
use App\Services\TeacherResolutionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherPortalController extends Controller
{
    public function __construct(
        private TeachingAssistant $assistant,
        private ExportService $exports,
        private TeacherResolutionService $teachers,
    ) {}

    protected function requireTeacher(Request $request, array $capabilities = ['isStaff']): Teacher
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->authorizeModuleAccess($request, capabilities: $capabilities);

        $teacher = $this->teachers->resolveForUser($user);
        abort_unless($teacher, 403, 'Teacher profile not linked to this account.');

        return $teacher;
    }

    /**
     * Teachers manage their own LMS items; school admins manage the whole school LMS.
     *
     * @return array{0: ?Teacher, 1: bool} [resolved teacher or null, is school LMS manager]
     */
    protected function resolveLmsActor(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->authorizeModuleAccess($request, capabilities: ['isStaff', 'canManageTeachers']);

        $teacher = $this->teachers->resolveForUser($user);
        $isSchoolManager = app(PermissionService::class)->hasCapability($user, 'canManageTeachers');

        abort_unless($teacher || $isSchoolManager, 403, 'Teacher profile not linked to this account.');

        return [$teacher, $isSchoolManager];
    }

    /**
     * Teachers submit/lock their own class registers; school managers may do so for any class.
     *
     * @return array{0: ?Teacher, 1: bool}
     */
    protected function resolveAttendanceActor(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->authorizeModuleAccess($request, capabilities: ['canManageStudents']);

        $teacher = $this->teachers->resolveForUser($user);
        $isSchoolManager = app(PermissionService::class)->hasCapability($user, 'canManageTeachers');

        abort_unless($teacher || $isSchoolManager, 403, 'Teacher profile not linked to this account.');

        return [$teacher, $isSchoolManager];
    }

    protected function schoolId(Request $request): int
    {
        return (int) $request->user()->school_id;
    }

    /** Dashboard widgets for the teacher portal. */
    public function dashboard(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);
        $today = now()->toDateString();

        $classIds = $this->teachers->assignedClassIds($teacher);

        $pendingAttendance = $classIds->filter(function ($classId) use ($schoolId, $today) {
            return ! Attendance::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->whereDate('date', $today)
                ->exists();
        })->count();

        $upcomingExams = Exam::query()
            ->where('school_id', $schoolId)
            ->whereDate('exam_date', '>=', $today)
            ->orderBy('exam_date')
            ->limit(8)
            ->get(['id', 'name', 'exam_date', 'status']);

        $announcements = Announcement::query()
            ->where('school_id', $schoolId)
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'created_at']);

        $unread = TeacherNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        $studentCount = Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('class_id', $classIds)
            ->count();

        return $this->success([
            'classes_count' => $classIds->count(),
            'students_count' => $studentCount,
            'pending_attendance_classes' => $pendingAttendance,
            'upcoming_exams' => $upcomingExams,
            'announcements' => $announcements,
            'unread_notifications' => $unread,
            'workload' => [
                'lesson_plans_draft' => LessonPlan::where('teacher_id', $teacher->id)->where('status', 'draft')->count(),
                'assignments_open' => Assignment::where('teacher_id', $teacher->id)->where('status', '!=', 'closed')->count(),
                'submissions_to_grade' => AssignmentSubmission::query()
                    ->whereHas('assignment', fn ($q) => $q->where('teacher_id', $teacher->id))
                    ->where('status', 'submitted')
                    ->count(),
            ],
        ]);
    }

    /** My Classes with students and behaviour history. */
    public function myClasses(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $assignments = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereNotNull('class_id')
            ->with(['classModel:id,name,teacher_id', 'subject:id,name'])
            ->get();

        $classes = $assignments->groupBy('class_id')->map(function ($rows, $classId) use ($schoolId) {
            $first = $rows->first();
            $classId = (int) $classId;
            $students = Student::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', SchoolDomainRules::inactiveStudentStatuses());
                })
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'student_number', 'status', 'class_id']);

            return [
                'class_id' => $classId,
                'class_name' => $first->classModel?->name ?? 'Unnamed class',
                'subjects' => $rows->pluck('subject.name')->filter()->unique()->values(),
                'student_count' => $students->count(),
                'students' => $students,
            ];
        })->values();

        return $this->success($classes);
    }

    public function classStudentDetail(Request $request, int $studentId)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $this->assertTeacherOwnsClass($teacher, $student->class_id);

        $attendance = Attendance::query()
            ->where('student_id', $studentId)
            ->orderByDesc('date')
            ->limit(30)
            ->get(['id', 'date', 'status', 'remarks']);

        $behaviour = BehaviorPoint::query()
            ->where('student_id', $studentId)
            ->orderByDesc('recorded_on')
            ->limit(20)
            ->get();

        $discipline = DisciplinaryRecord::query()
            ->where('student_id', $studentId)
            ->orderByDesc('incident_date')
            ->limit(20)
            ->get();

        $canViewParents = $request->user()?->role?->canManageTeachers()
            || app(PermissionService::class)->hasCapability($request->user(), 'canManageTeachers');

        return $this->success([
            'student' => $student->only(['id', 'full_name', 'student_number', 'status', 'class_id', 'gender', 'date_of_birth']),
            'attendance_history' => $attendance,
            'behaviour_history' => $behaviour,
            'discipline_history' => $discipline,
            'parent_contacts' => $canViewParents ? [
                'guardian' => $student->guardian,
                'phone' => $student->guardian_phone ?? null,
                'email' => $student->guardian_email ?? null,
            ] : null,
        ]);
    }

    // —— Attendance extras ——

    public function submitAttendance(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveAttendanceActor($request);
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'subject_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'string', 'max:50'],
        ]);

        $classId = (int) $data['class_id'];
        $this->assertClassInSchool($request, $classId);

        if (! $isSchoolManager) {
            $this->assertTeacherOwnsClass($teacher, $classId);
            if (! empty($data['subject_id'])) {
                $this->assertTeacherOwnsSubject($teacher, $data['subject_id'], $classId);
            }
        }

        $session = AttendanceSession::query()->updateOrCreate(
            [
                'school_id' => $this->schoolId($request),
                'class_id' => $data['class_id'],
                'date' => $data['date'],
                'subject_id' => $data['subject_id'] ?? null,
                'period' => $data['period'] ?? null,
            ],
            [
                'teacher_id' => $teacher?->id,
                'submitted_at' => now(),
            ]
        );

        Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->where('class_id', $data['class_id'])
            ->whereDate('date', $data['date'])
            ->whereNull('submitted_at')
            ->update(['submitted_at' => now()]);

        return $this->success($session, 'Attendance submitted');
    }

    public function lockAttendance(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveAttendanceActor($request);
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'subject_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'string', 'max:50'],
        ]);

        $classId = (int) $data['class_id'];
        $this->assertClassInSchool($request, $classId);

        if (! $isSchoolManager) {
            $this->assertTeacherOwnsClass($teacher, $classId);
            if (! empty($data['subject_id'])) {
                $this->assertTeacherOwnsSubject($teacher, $data['subject_id'], $classId);
            }
        }

        $session = AttendanceSession::query()->updateOrCreate(
            [
                'school_id' => $this->schoolId($request),
                'class_id' => $data['class_id'],
                'date' => $data['date'],
                'subject_id' => $data['subject_id'] ?? null,
                'period' => $data['period'] ?? null,
            ],
            [
                'teacher_id' => $teacher?->id,
                'submitted_at' => now(),
                'locked_at' => now(),
                'locked_by' => $request->user()->id,
            ]
        );

        $attendanceQuery = Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->where('class_id', $data['class_id'])
            ->whereDate('date', $data['date']);

        // Set submitted_at only when missing (avoid MySQL-only NOW() for SQLite).
        (clone $attendanceQuery)
            ->whereNull('submitted_at')
            ->update(['submitted_at' => now()]);

        $attendanceQuery->update([
            'locked_at' => now(),
            'locked_by' => $request->user()->id,
        ]);

        return $this->success($session, 'Attendance locked');
    }

    public function attendanceReports(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'class', 'student', 'chronic'])],
            'student_id' => ['nullable', 'integer'],
            'format' => ['nullable', Rule::in(['json', 'csv', 'print'])],
        ]);
        $this->assertTeacherOwnsClass($teacher, (int) $data['class_id']);

        $from = $data['from'] ?? now()->subDays(30)->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $type = $data['type'] ?? 'class';

        $query = Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->where('class_id', $data['class_id'])
            ->whereBetween('date', [$from, $to])
            ->with('student:id,full_name,student_number');

        if (! empty($data['student_id'])) {
            $query->where('student_id', $data['student_id']);
        }

        $rows = $query->orderBy('date')->get();

        if ($type === 'chronic') {
            $totals = $rows->groupBy('student_id')->map(function ($group) {
                $absent = $group->whereIn('status', ['absent', 'sick'])->count();
                $total = $group->count();
                $rate = $total > 0 ? round(($absent / $total) * 100, 1) : 0;

                return [
                    'student_id' => $group->first()->student_id,
                    'student' => $group->first()->student?->full_name,
                    'absent_days' => $absent,
                    'total_days' => $total,
                    'absence_rate' => $rate,
                    'chronic' => $rate >= 20,
                ];
            })->values()->filter(fn ($r) => $r['chronic'])->values();

            return $this->exportOrJson($request, $data['format'] ?? 'json', 'chronic-absence', [
                'Student', 'Absent days', 'Total days', 'Absence %',
            ], $totals->map(fn ($r) => [$r['student'], $r['absent_days'], $r['total_days'], $r['absence_rate']])->all(), $totals);
        }

        $payload = $rows->map(fn ($r) => [
            'date' => $r->date?->toDateString(),
            'student' => $r->student?->full_name,
            'status' => $r->status,
            'remarks' => $r->remarks,
        ]);

        return $this->exportOrJson($request, $data['format'] ?? 'json', 'attendance-report', [
            'Date', 'Student', 'Status', 'Remarks',
        ], $payload->map(fn ($r) => array_values($r))->all(), $payload);
    }

    // —— Timetable ——

    public function freePeriods(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $slots = Timetable::query()
            ->where('school_id', $this->schoolId($request))
            ->where('teacher_id', $teacher->id)
            ->get(['day', 'start_time', 'end_time']);

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00'];
        $busy = $slots->map(fn ($s) => $s->day.'|'.substr((string) $s->start_time, 0, 5))->flip();

        $free = [];
        foreach ($days as $day) {
            foreach ($periods as $p) {
                if (! $busy->has($day.'|'.$p)) {
                    $free[] = ['day' => $day, 'start_time' => $p];
                }
            }
        }

        return $this->success($free);
    }

    public function examTimetable(Request $request)
    {
        $this->requireTeacher($request);
        $exams = Exam::query()
            ->where('school_id', $this->schoolId($request))
            ->whereDate('exam_date', '>=', now()->subDays(7))
            ->orderBy('exam_date')
            ->limit(50)
            ->get();

        return $this->success($exams);
    }

    public function timetableChangeRequests(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            TimetableChangeRequest::query()
                ->where('teacher_id', $teacher->id)
                ->latest()
                ->limit(50)
                ->get()
        );
    }

    public function storeTimetableChangeRequest(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'request_type' => ['required', Rule::in(['change', 'conflict'])],
            'details' => ['required', 'string', 'max:5000'],
            'preferred_slot' => ['nullable', 'string', 'max:255'],
        ]);

        $row = TimetableChangeRequest::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            ...$data,
            'status' => 'pending',
        ]);

        return $this->created($row, 'Timetable request submitted');
    }

    // —— Calendar ——

    public function calendar(Request $request)
    {
        $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $events = SchoolEvent::query()
            ->where('school_id', $schoolId)
            ->orderBy('starts_at')
            ->limit(100)
            ->get();

        $deadlines = Assignment::query()
            ->where('teacher_id', $request->user()->teacher?->id ?? 0)
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(50)
            ->get(['id', 'title', 'due_date', 'class_id']);

        return $this->success([
            'events' => $events,
            'assignment_deadlines' => $deadlines,
            'exams' => Exam::query()->where('school_id', $schoolId)->whereDate('exam_date', '>=', now())->limit(30)->get(),
        ]);
    }

    // —— Lesson plans ——

    public function lessonPlans(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            LessonPlan::query()
                ->where('teacher_id', $teacher->id)
                ->with(['classModel:id,name', 'subject:id,name'])
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function storeLessonPlan(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'plan_type' => ['nullable', Rule::in(['lesson', 'weekly', 'term', 'topic'])],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'planned_date' => ['nullable', 'date'],
            'topic' => ['nullable', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'outcomes' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
            'resources' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'completed'])],
            'copy_from_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['copy_from_id'])) {
            $source = LessonPlan::query()->where('teacher_id', $teacher->id)->findOrFail($data['copy_from_id']);
            $data = array_merge($source->only([
                'class_id', 'subject_id', 'plan_type', 'topic', 'objectives', 'outcomes', 'activities', 'resources',
            ]), array_filter($data, fn ($v) => $v !== null), [
                'title' => $data['title'] ?: ($source->title.' (copy)'),
            ]);
            unset($data['copy_from_id']);
        }

        if (! empty($data['class_id'])) {
            $this->assertTeacherOwnsClass($teacher, $data['class_id']);
        }
        if (! empty($data['subject_id'])) {
            $this->assertTeacherOwnsSubject(
                $teacher,
                $data['subject_id'],
                ! empty($data['class_id']) ? (int) $data['class_id'] : null,
            );
        }

        $plan = LessonPlan::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'plan_type' => $data['plan_type'] ?? 'lesson',
            'status' => $data['status'] ?? 'draft',
            ...collect($data)->except(['copy_from_id', 'plan_type', 'status'])->all(),
        ]);

        return $this->created($plan->load(['classModel:id,name', 'subject:id,name']));
    }

    public function updateLessonPlan(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request);
        $plan = LessonPlan::query()->where('teacher_id', $teacher->id)->findOrFail($id);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'plan_type' => ['sometimes', Rule::in(['lesson', 'weekly', 'term', 'topic'])],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'planned_date' => ['nullable', 'date'],
            'topic' => ['nullable', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'outcomes' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
            'resources' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['draft', 'submitted', 'completed'])],
        ]);

        if (($data['status'] ?? null) === 'completed') {
            $data['completed_at'] = now();
        }
        if (($data['status'] ?? null) === 'submitted') {
            $data['submitted_at'] = now();
        }

        $plan->update($data);

        return $this->success($plan->fresh()->load(['classModel:id,name', 'subject:id,name']));
    }

    // —— Syllabus ——

    public function syllabusTopics(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $q = SyllabusTopic::query()->where('teacher_id', $teacher->id)->with(['subject:id,name', 'classModel:id,name']);
        if ($request->filled('subject_id')) {
            $q->where('subject_id', $request->integer('subject_id'));
        }

        return $this->success($q->orderBy('sort_order')->limit(200)->get());
    }

    public function storeSyllabusTopic(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'subject_id' => ['required', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'chapter' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'objectives' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['planned', 'in_progress', 'completed'])],
            'coverage_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        if (! empty($data['class_id'])) {
            $this->assertTeacherOwnsClass($teacher, $data['class_id']);
        }
        $this->assertTeacherOwnsSubject(
            $teacher,
            $data['subject_id'],
            ! empty($data['class_id']) ? (int) $data['class_id'] : null,
        );

        $topic = SyllabusTopic::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'status' => $data['status'] ?? 'planned',
            'coverage_percent' => $data['coverage_percent'] ?? 0,
            ...collect($data)->except(['status', 'coverage_percent'])->all(),
        ]);

        return $this->created($topic);
    }

    public function updateSyllabusTopic(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request);
        $topic = SyllabusTopic::query()->where('teacher_id', $teacher->id)->findOrFail($id);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'chapter' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'objectives' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['planned', 'in_progress', 'completed'])],
            'coverage_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);
        if (($data['status'] ?? null) === 'completed') {
            $data['completed_on'] = now()->toDateString();
            $data['coverage_percent'] = $data['coverage_percent'] ?? 100;
        }
        $topic->update($data);

        return $this->success($topic->fresh());
    }

    // —— Resources ——

    public function resources(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $q = TeachingResource::query()
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)->orWhere('shared', true);
            })
            ->where('school_id', $this->schoolId($request))
            ->with(['subject:id,name', 'classModel:id,name'])
            ->latest();

        foreach (['subject_id', 'class_id', 'resource_type', 'topic', 'term'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->input($f));
            }
        }

        return $this->success($q->limit(200)->get());
    }

    public function storeResource(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'resource_type' => ['nullable', 'string', 'max:50'],
            'subject_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'topic' => ['nullable', 'string', 'max:255'],
            'term' => ['nullable', 'string', 'max:100'],
            'file_url' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'shared' => ['nullable', 'boolean'],
        ]);

        $row = TeachingResource::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'resource_type' => $data['resource_type'] ?? 'notes',
            'shared' => (bool) ($data['shared'] ?? false),
            ...collect($data)->except(['resource_type', 'shared'])->all(),
        ]);

        return $this->created($row);
    }

    // —— Homework submissions ——

    public function assignmentSubmissions(Request $request, int $assignmentId)
    {
        $teacher = $this->requireTeacher($request);
        Assignment::query()->where('teacher_id', $teacher->id)->findOrFail($assignmentId);

        return $this->success(
            AssignmentSubmission::query()
                ->where('assignment_id', $assignmentId)
                ->with('student:id,full_name,student_number')
                ->latest()
                ->get()
        );
    }

    public function gradeSubmission(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request, ['canEnterExamResults']);
        $sub = AssignmentSubmission::query()
            ->whereHas('assignment', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->findOrFail($id);

        $data = $request->validate([
            'score' => ['nullable', 'numeric'],
            'teacher_comment' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['graded', 'returned', 'resubmit'])],
        ]);

        $sub->update([
            ...$data,
            'graded_at' => now(),
            'returned_at' => in_array($data['status'], ['returned', 'resubmit'], true) ? now() : $sub->returned_at,
        ]);

        return $this->success($sub->fresh()->load('student:id,full_name'));
    }

    public function storeSubmission(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'file_url' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['submitted', 'graded', 'returned', 'resubmit'])],
        ]);
        Assignment::query()->where('teacher_id', $teacher->id)->findOrFail($data['assignment_id']);

        $sub = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $data['assignment_id'],
                'student_id' => $data['student_id'],
            ],
            [
                'school_id' => $this->schoolId($request),
                'file_url' => $data['file_url'] ?? null,
                'content' => $data['content'] ?? null,
                'status' => $data['status'] ?? 'submitted',
                'submitted_at' => now(),
            ]
        );

        return $this->created($sub);
    }

    // —— LMS / Online lessons ——

    public function onlineLessons(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveLmsActor($request);
        $schoolId = $this->schoolId($request);

        $query = OnlineLesson::query()
            ->where('school_id', $schoolId)
            ->with(['classModel:id,name', 'subject:id,name', 'teacher:id,name,email']);

        if (! $isSchoolManager) {
            $query->where('teacher_id', $teacher->id);
        }

        return $this->success(
            $query->latest('scheduled_at')->limit(100)->get()
        );
    }

    public function storeOnlineLesson(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveLmsActor($request);
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'lesson_type' => ['nullable', Rule::in(['live', 'recorded', 'discussion', 'quiz', 'poll'])],
            'teacher_id' => [
                $isSchoolManager && ! $teacher ? 'required' : 'nullable',
                'integer',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
            'meeting_url' => ['nullable', 'string', 'max:2048'],
            'recording_url' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $teacherId = isset($data['teacher_id']) ? (int) $data['teacher_id'] : null;
        if (! $teacherId && $teacher) {
            $teacherId = $teacher->id;
        }
        abort_unless($teacherId, 422, 'Select a teacher for this online lesson.');

        $row = OnlineLesson::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacherId,
            'lesson_type' => $data['lesson_type'] ?? 'live',
            'status' => $data['status'] ?? 'scheduled',
            // Stub meeting URL when live lesson has none — real video later.
            'meeting_url' => $data['meeting_url'] ?? (($data['lesson_type'] ?? 'live') === 'live'
                ? 'https://meet.example.local/room/'.uniqid('lesson_')
                : null),
            ...collect($data)->except(['lesson_type', 'status', 'meeting_url', 'teacher_id'])->all(),
        ]);

        return $this->created($row->load(['teacher:id,name', 'classModel:id,name', 'subject:id,name']));
    }

    // —— Report cards ——

    public function reportCards(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            ReportCardNarrative::query()
                ->where('teacher_id', $teacher->id)
                ->with(['student:id,full_name', 'classModel:id,name', 'term:id,name'])
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function storeReportCard(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canEnterExamResults']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
            'academic_comment' => ['nullable', 'string'],
            'behaviour_comment' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'areas_for_improvement' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'submitted'])],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $data['class_id'] ?? $student->class_id);

        $row = ReportCardNarrative::query()->updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'student_id' => $data['student_id'],
                'term_id' => $data['term_id'] ?? null,
            ],
            [
                'school_id' => $this->schoolId($request),
                'class_id' => $data['class_id'] ?? $student->class_id,
                'academic_comment' => $data['academic_comment'] ?? null,
                'behaviour_comment' => $data['behaviour_comment'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
                'strengths' => $data['strengths'] ?? null,
                'areas_for_improvement' => $data['areas_for_improvement'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'submitted_at' => ($data['status'] ?? null) === 'submitted' ? now() : null,
            ]
        );

        return $this->success($row->load(['student:id,full_name']));
    }

    // —— Behaviour & support ——

    public function behaviourPoints(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $classIds = $this->teacherClassIds($teacher);

        $q = BehaviorPoint::query()
            ->where('school_id', $this->schoolId($request))
            ->whereHas('student', fn ($s) => $s->whereIn('class_id', $classIds))
            ->with('student:id,full_name,class_id')
            ->latest('recorded_on');

        if ($request->filled('student_id')) {
            $q->where('student_id', $request->integer('student_id'));
        }

        return $this->success($q->limit(100)->get());
    }

    public function storeBehaviourPoint(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'points' => ['required', 'integer'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'recorded_on' => ['nullable', 'date'],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $student->class_id);

        $row = BehaviorPoint::create([
            'school_id' => $this->schoolId($request),
            'student_id' => $data['student_id'],
            'points' => $data['points'],
            'category' => $data['category'],
            'description' => $data['description'],
            'recorded_by' => $request->user()->id,
            'recorded_on' => $data['recorded_on'] ?? now()->toDateString(),
        ]);

        return $this->created($row);
    }

    public function interventions(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $classIds = $this->teacherClassIds($teacher);

        return $this->success(
            StudentIntervention::query()
                ->where('school_id', $this->schoolId($request))
                ->whereHas('student', fn ($s) => $s->whereIn('class_id', $classIds))
                ->with('student:id,full_name')
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function storeIntervention(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'intervention_type' => ['required', 'string', 'max:100'],
            'summary' => ['required', 'string'],
            'action_plan' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $student->class_id);

        $row = StudentIntervention::create([
            'school_id' => $this->schoolId($request),
            'student_id' => $data['student_id'],
            'intervention_type' => $data['intervention_type'],
            'summary' => $data['summary'],
            'action_plan' => $data['action_plan'] ?? null,
            'assigned_to' => $request->user()->id,
            'created_by' => $request->user()->id,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'status' => 'open',
        ]);

        return $this->created($row);
    }

    public function participation(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            ClassParticipationRecord::query()
                ->where('teacher_id', $teacher->id)
                ->with('student:id,full_name')
                ->latest('recorded_on')
                ->limit(100)
                ->get()
        );
    }

    public function storeParticipation(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canEnterExamResults']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'recorded_on' => ['nullable', 'date'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $data['class_id'] ?? $student->class_id);

        $row = ClassParticipationRecord::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'recorded_on' => $data['recorded_on'] ?? now()->toDateString(),
            ...collect($data)->except(['recorded_on'])->all(),
        ]);

        return $this->created($row);
    }

    // —— Department ——

    public function department(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $peers = Teacher::query()
            ->where('school_id', $schoolId)
            ->when($teacher->department, fn ($q) => $q->where('department', $teacher->department))
            ->get(['id', 'name', 'department', 'subject', 'email']);

        $shared = TeachingResource::query()
            ->where('school_id', $schoolId)
            ->where('shared', true)
            ->latest()
            ->limit(30)
            ->get();

        return $this->success([
            'department' => $teacher->department,
            'peers' => $peers,
            'shared_resources' => $shared,
        ]);
    }

    // —— Leave ——

    public function myLeave(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        $rows = LeaveRequest::query()
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->limit(50)
            ->get();

        $used = LeaveRequest::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('days');

        return $this->success([
            'balance' => [
                'annual_allowance' => 21,
                'used' => (int) $used,
                'remaining' => max(0, 21 - (int) $used),
            ],
            'requests' => $rows,
        ]);
    }

    public function applyLeave(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'leave_type_id' => [
                'nullable',
                'integer',
                Rule::exists('leave_types', 'id')->where(fn ($q) => $q->where('school_id', $this->schoolId($request))->where('is_active', true)),
            ],
            'type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'request_replacement' => ['nullable', 'boolean'],
        ]);

        if (empty($data['leave_type_id']) && empty($data['type'])) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['type' => ['Select a leave type.']],
            ], 422);
        }

        $leaveType = null;
        if (! empty($data['leave_type_id'])) {
            $leaveType = \App\Models\LeaveType::query()
                ->where('school_id', $this->schoolId($request))
                ->findOrFail($data['leave_type_id']);
        }

        $start = $request->date('start_date');
        $end = $request->date('end_date');
        $days = $start->diffInDays($end) + 1;

        $leave = LeaveRequest::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'leave_type_id' => $leaveType?->id,
            'requested_by' => $request->user()->id,
            'type' => $leaveType?->code ?: ($leaveType?->name ?: $data['type']),
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        if ($request->boolean('request_replacement')) {
            $classIds = $this->teacherClassIds($teacher);
            foreach ($classIds->take(5) as $classId) {
                ClassSubstitution::create([
                    'school_id' => $this->schoolId($request),
                    'class_id' => $classId,
                    'absent_teacher_id' => $teacher->id,
                    'leave_request_id' => $leave->id,
                    'date' => $start->toDateString(),
                    'status' => 'open',
                    'notes' => 'Auto-created from leave request',
                ]);
            }
        }

        return $this->created($leave, 'Leave request submitted');
    }

    // —— Substitutions ——

    public function substitutions(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success([
            'open' => ClassSubstitution::query()
                ->where('school_id', $this->schoolId($request))
                ->where('status', 'open')
                ->where('absent_teacher_id', '!=', $teacher->id)
                ->with(['classModel:id,name', 'absentTeacher:id,name', 'subject:id,name'])
                ->orderBy('date')
                ->limit(50)
                ->get(),
            'mine' => ClassSubstitution::query()
                ->where(function ($q) use ($teacher) {
                    $q->where('substitute_teacher_id', $teacher->id)
                        ->orWhere('absent_teacher_id', $teacher->id);
                })
                ->with(['classModel:id,name', 'absentTeacher:id,name', 'substituteTeacher:id,name'])
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function acceptSubstitution(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request);
        $row = ClassSubstitution::query()
            ->where('school_id', $this->schoolId($request))
            ->where('status', 'open')
            ->findOrFail($id);

        $row->update([
            'substitute_teacher_id' => $teacher->id,
            'status' => 'accepted',
        ]);

        return $this->success($row->fresh(), 'Cover lesson accepted');
    }

    // —— Notifications ——

    public function notifications(Request $request)
    {
        $this->requireTeacher($request);

        return $this->success(
            TeacherNotification::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function markNotificationRead(Request $request, int $id)
    {
        $this->requireTeacher($request);
        $n = TeacherNotification::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $n->update(['read_at' => now()]);

        return $this->success($n);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $this->requireTeacher($request);
        TeacherNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked read');
    }

    // —— AI ——

    public function aiGenerate(Request $request)
    {
        $this->requireTeacher($request);
        $data = $request->validate([
            'task' => ['required', 'string', 'max:100'],
            'context' => ['nullable', 'array'],
        ]);

        return $this->success($this->assistant->generate($data['task'], $data['context'] ?? []));
    }

    // —— Export suite ——

    public function export(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $permissions = app(PermissionService::class);
        abort_unless(
            $permissions->hasPermission($request->user(), 'reports.view')
                || $permissions->hasPermission($request->user(), 'reports.generate'),
            403,
            'You do not have permission to export reports.'
        );
        $data = $request->validate([
            'type' => ['required', Rule::in([
                'attendance', 'marksheet', 'class_list', 'assignments', 'exams', 'progress', 'lesson_plans',
            ])],
            'class_id' => ['nullable', 'integer'],
            'format' => ['nullable', Rule::in(['csv', 'print'])],
        ]);
        $format = $data['format'] ?? 'csv';
        $schoolId = $this->schoolId($request);

        return match ($data['type']) {
            'class_list' => $this->exportClassList($request, $teacher, $data['class_id'] ?? null, $format),
            'lesson_plans' => $this->exportOrJson($request, $format, 'lesson-plans', [
                'Title', 'Type', 'Topic', 'Status', 'Date',
            ], LessonPlan::where('teacher_id', $teacher->id)->latest()->limit(200)->get()
                ->map(fn ($p) => [$p->title, $p->plan_type, $p->topic, $p->status, $p->planned_date?->toDateString()])->all()),
            'assignments' => $this->exportOrJson($request, $format, 'assignments', [
                'Title', 'Due', 'Marks', 'Status',
            ], Assignment::where('teacher_id', $teacher->id)->latest()->limit(200)->get()
                ->map(fn ($a) => [$a->title, $a->due_date?->toDateString(), $a->total_marks, $a->status])->all()),
            'exams' => $this->exportOrJson($request, $format, 'exams', [
                'Name', 'Date', 'Published',
            ], Exam::where('school_id', $schoolId)->orderByDesc('exam_date')->limit(200)->get()
                ->map(fn ($e) => [$e->name, $e->exam_date?->toDateString(), $e->is_published ? 'yes' : 'no'])->all()),
            'attendance' => $this->exportTeacherAttendance($request, $teacher, $data['class_id'] ?? null, $format),
            'marksheet' => $this->exportTeacherMarksheet($request, $teacher, $data['class_id'] ?? null, $format),
            'progress' => $this->exportTeacherProgress($request, $teacher, $data['class_id'] ?? null, $format),
            default => $this->exportClassList($request, $teacher, $data['class_id'] ?? null, $format),
        };
    }

    protected function exportTeacherAttendance(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        $classIds = $classId
            ? tap(collect([(int) $classId]), fn ($ids) => $this->assertTeacherOwnsClass($teacher, $classId))
            : $this->teacherClassIds($teacher);

        $rows = Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->whereIn('class_id', $classIds->isEmpty() ? [0] : $classIds->all())
            ->with(['student:id,full_name,student_number', 'classModel:id,name'])
            ->orderByDesc('date')
            ->limit(500)
            ->get()
            ->map(fn ($a) => [
                $a->date?->toDateString() ?? $a->date,
                $a->classModel?->name,
                $a->student?->full_name,
                $a->student?->student_number,
                $a->status,
                $a->remarks,
            ])
            ->all();

        return $this->exportOrJson($request, $format, 'attendance', [
            'Date', 'Class', 'Student', 'Student number', 'Status', 'Remarks',
        ], $rows);
    }

    protected function exportTeacherMarksheet(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        $classIds = $classId
            ? tap(collect([(int) $classId]), fn ($ids) => $this->assertTeacherOwnsClass($teacher, $classId))
            : $this->teacherClassIds($teacher);

        $rows = \App\Models\Grade::query()
            ->whereIn('class_id', $classIds->isEmpty() ? [0] : $classIds->all())
            ->when($teacher->school_id, fn ($q) => $q->where('school_id', $teacher->school_id))
            ->with('student:id,full_name,student_number')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get()
            ->map(fn ($g) => [
                $g->student?->full_name,
                $g->student?->student_number,
                $g->subject,
                $g->score,
                $g->total,
                $g->grade,
                $g->term,
                $g->year,
            ])
            ->all();

        return $this->exportOrJson($request, $format, 'marksheet', [
            'Student', 'Student number', 'Subject', 'Score', 'Total', 'Grade', 'Term', 'Year',
        ], $rows);
    }

    protected function exportTeacherProgress(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        $classIds = $classId
            ? tap(collect([(int) $classId]), fn ($ids) => $this->assertTeacherOwnsClass($teacher, $classId))
            : $this->teacherClassIds($teacher);

        $rows = SyllabusTopic::query()
            ->where('teacher_id', $teacher->id)
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when(! $classId && $classIds->isNotEmpty(), fn ($q) => $q->whereIn('class_id', $classIds->all()))
            ->orderBy('title')
            ->limit(500)
            ->get()
            ->map(fn ($t) => [
                $t->title,
                $t->chapter,
                $t->status,
                $t->coverage_percent,
                $t->class_id,
                $t->subject_id,
            ])
            ->all();

        return $this->exportOrJson($request, $format, 'progress', [
            'Topic', 'Chapter', 'Status', 'Coverage %', 'Class ID', 'Subject ID',
        ], $rows);
    }

    protected function exportClassList(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        if ($classId) {
            $this->assertTeacherOwnsClass($teacher, $classId);
            $students = Student::query()
                ->where('school_id', $this->schoolId($request))
                ->where('class_id', $classId)
                ->orderBy('full_name')
                ->get(['full_name', 'student_number', 'status']);
        } else {
            $students = Student::query()
                ->where('school_id', $this->schoolId($request))
                ->whereIn('class_id', $this->teacherClassIds($teacher))
                ->orderBy('full_name')
                ->get(['full_name', 'student_number', 'status']);
        }

        return $this->exportOrJson($request, $format, 'class-list', [
            'Name', 'Student number', 'Status',
        ], $students->map(fn ($s) => [$s->full_name, $s->student_number, $s->status])->all());
    }

    protected function exportOrJson(Request $request, string $format, string $filename, array $headers, array $rows, mixed $jsonPayload = null)
    {
        if ($format === 'csv') {
            return $this->exports->csv($filename, $headers, $rows);
        }
        if ($format === 'print') {
            return $this->exports->printableHtml(ucwords(str_replace('-', ' ', $filename)), $this->exports->htmlTable($headers, $rows));
        }

        return $this->success($jsonPayload ?? $rows);
    }

    protected function teacherClassIds(Teacher $teacher)
    {
        return $this->teachers->assignedClassIds($teacher);
    }

    protected function assertClassInSchool(Request $request, int $classId): void
    {
        $exists = ClassModel::query()
            ->where('school_id', $this->schoolId($request))
            ->whereKey($classId)
            ->exists();

        abort_unless($exists, 422, 'Class not found in this school.');
    }

    protected function assertTeacherOwnsClass(Teacher $teacher, mixed $classId): void
    {
        $this->teachers->assertOwnsClass($teacher, $classId);
    }

    protected function assertTeacherOwnsSubject(Teacher $teacher, mixed $subjectId, ?int $classId = null): void
    {
        $this->teachers->assertOwnsSubject($teacher, $subjectId, $classId);
    }
}
