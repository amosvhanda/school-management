<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\EnrollmentApplication;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Employee;
use App\Models\Announcement;
use App\Models\SchoolEvent;
use App\Models\ExamResult;
use App\Models\LessonPlan;
use App\Models\OnlineLesson;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function kpis(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $today = now()->toDateString();

        $studentQuery = Student::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $teacherQuery = Teacher::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $classQuery = ClassModel::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $userQuery = User::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $invoiceQuery = Invoice::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $paymentQuery = Payment::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $attendanceQuery = Attendance::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $payrollQuery = Payroll::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $transactionQuery = Transaction::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $totalStudents = (clone $studentQuery)->count();
        $activeStudents = (clone $studentQuery)->where('status', 'active')->count();
        $totalTeachers = (clone $teacherQuery)->where('status', 'active')->count();
        $totalClasses = (clone $classQuery)->count();
        $totalParents = (clone $userQuery)->where('role', UserRole::Parent)->count();
        $totalUsers = (clone $userQuery)->count();

        $outstandingFees = (clone $invoiceQuery)->where('status', '!=', 'paid')->sum('balance');
        $paymentsToday = (clone $paymentQuery)->whereDate('date', $today)->where('status', 'completed')->sum('amount');
        $totalRevenue = (clone $paymentQuery)->where('status', 'completed')->sum('amount');

        $attendanceToday = (clone $attendanceQuery)->whereDate('date', $today)->get();
        $attendanceSummary = [
            'present' => $attendanceToday->where('status', 'present')->count(),
            'absent' => $attendanceToday->where('status', 'absent')->count(),
            'late' => $attendanceToday->where('status', 'late')->count(),
            'excused' => $attendanceToday->where('status', 'excused')->count(),
            'half_day' => $attendanceToday->where('status', 'half_day')->count(),
            'total' => $attendanceToday->count(),
            'date' => $today,
        ];

        $currentMonth = now()->month;
        $currentYear = now()->year;
        $payrollMonthQuery = (clone $payrollQuery)->where('month', $currentMonth)->where('year', $currentYear);
        $payrollTotal = (clone $payrollMonthQuery)->sum('gross_salary');
        $payrollPaid = (clone $payrollMonthQuery)->sum('amount_paid');
        $payrollPending = (clone $payrollMonthQuery)->where('status', 'pending')->sum('net_salary');
        $payrollPartial = (clone $payrollMonthQuery)->where('status', 'partial')->sum('net_salary');

        $thisMonthStudents = (clone $studentQuery)->whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count();
        $lastMonthStudents = (clone $studentQuery)->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $studentsGrowth = $lastMonthStudents > 0
            ? (($thisMonthStudents - $lastMonthStudents) / $lastMonthStudents) * 100
            : 0;

        $thisMonthPayments = (clone $paymentQuery)->whereMonth('date', $currentMonth)->whereYear('date', $currentYear)->sum('amount');
        $lastMonthPayments = (clone $paymentQuery)->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year)->sum('amount');
        $paymentsGrowth = $lastMonthPayments > 0
            ? (($thisMonthPayments - $lastMonthPayments) / $lastMonthPayments) * 100
            : 0;

        $thisMonthUsers = (clone $userQuery)->whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count();
        $lastMonthUsers = (clone $userQuery)->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $usersChange = $lastMonthUsers > 0 ? (($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100 : 0;

        $thisMonthActivity = (clone $transactionQuery)->whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count();
        $lastMonthActivity = (clone $transactionQuery)->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $activityChange = $lastMonthActivity > 0 ? (($thisMonthActivity - $lastMonthActivity) / $lastMonthActivity) * 100 : 0;

        $totalErrors = (clone $transactionQuery)->whereIn('status', ['pending', 'cancelled'])->count();
        $errorsChange = 0;

        $pendingEnrollments = EnrollmentApplication::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'pending')
            ->count();

        $pendingLeaveRequests = LeaveRequest::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'data' => [
                'totalStudents' => $totalStudents,
                'totalParents' => $totalParents,
                'totalTeachers' => $totalTeachers,
                'totalClasses' => $totalClasses,
                'activeStudents' => $activeStudents,
                'outstandingFees' => round($outstandingFees, 2),
                'paymentsToday' => round($paymentsToday, 2),
                'attendanceSummary' => $attendanceSummary,
                'payrollSummary' => [
                    'total_payroll' => (float) $payrollTotal,
                    'total_paid' => (float) $payrollPaid,
                    'total_pending' => (float) $payrollPending,
                    'total_partial' => (float) $payrollPartial,
                ],
                'totalUsers' => $totalUsers,
                'totalRevenue' => (float) $totalRevenue,
                'totalActivity' => $thisMonthActivity,
                'totalErrors' => $totalErrors,
                'studentsGrowth' => round($studentsGrowth, 1),
                'paymentsGrowth' => round($paymentsGrowth, 1),
                'usersChange' => round($usersChange, 1),
                'activityChange' => round($activityChange, 1),
                'revenueChange' => round($paymentsGrowth, 1),
                'errorsChange' => round($errorsChange, 1),
                'pendingEnrollments' => $pendingEnrollments,
                'pendingLeaveRequests' => $pendingLeaveRequests,
                'totalStaff' => Employee::query()
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('status', 'active')
                    ->count(),
                'incomeThisMonth' => round((float) (clone $transactionQuery)
                    ->where('type', 'income')
                    ->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->sum('credit'), 2),
                'expenseThisMonth' => round((float) (clone $transactionQuery)
                    ->where('type', 'expense')
                    ->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->sum('debit'), 2),
                'collectedThisMonth' => round((float) $thisMonthPayments, 2),
                'newAdmissionsThisMonth' => $thisMonthStudents,
            ],
        ]);
    }

    /**
     * EduDash-style school dashboard widgets (notices, leaves, events, leaderboards).
     */
    public function schoolWidgets(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $notices = Announcement::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->with('creator:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Announcement $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'message' => \Illuminate\Support\Str::limit((string) $a->message, 120),
                'author' => $a->creator?->name ?? 'Admin',
                'date' => $a->date?->toDateString(),
            ]);

        $leaveRequests = LeaveRequest::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'pending')
            ->with('teacher:id,name,department')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (LeaveRequest $leave) => [
                'id' => $leave->id,
                'teacher_name' => $leave->teacher?->name ?? 'Staff member',
                'department' => $leave->teacher?->department,
                'type' => $leave->type,
                'days' => $leave->days,
                'start_date' => $leave->start_date?->toDateString(),
                'end_date' => $leave->end_date?->toDateString(),
                'applied_on' => $leave->created_at?->toDateString(),
            ]);

        $events = SchoolEvent::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('starts_at', '>=', now()->startOfDay())
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(fn (SchoolEvent $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'location' => $event->location,
                'starts_at' => optional($event->starts_at)->toIso8601String(),
                'ends_at' => optional($event->ends_at)->toIso8601String(),
                'type' => $event->type,
            ]);

        $topTeachers = Teacher::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'subject', 'department'])
            ->map(fn (Teacher $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'subject' => $t->subject,
                'department' => $t->department,
            ]);

        $topRows = ExamResult::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('percentage')
            ->selectRaw('student_id, AVG(percentage) as avg_percentage, COUNT(*) as result_count')
            ->groupBy('student_id')
            ->orderByDesc('avg_percentage')
            ->limit(5)
            ->get();

        $studentsById = Student::query()
            ->whereIn('id', $topRows->pluck('student_id')->filter()->all() ?: [0])
            ->with('classModel:id,name')
            ->get()
            ->keyBy('id');

        $topStudents = $topRows->map(function ($row) use ($studentsById) {
            $student = $studentsById->get($row->student_id);

            return [
                'id' => $row->student_id,
                'name' => $student?->full_name ?? 'Student',
                'class_name' => $student?->classModel?->name,
                'marks' => round((float) $row->avg_percentage, 1),
                'result_count' => (int) $row->result_count,
            ];
        });

        $newAdmissions = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->with('classModel:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'full_name', 'first_name', 'last_name', 'created_at', 'class_id', 'class'])
            ->map(fn (Student $s) => [
                'id' => $s->id,
                'name' => $s->full_name,
                'class_name' => $s->classModel?->name
                    ?? (is_string($s->class) && $s->class !== '' ? $s->class : null),
                'joined_on' => $s->created_at?->toDateString(),
            ]);

        return response()->json([
            'data' => [
                'notices' => $notices,
                'leave_requests' => $leaveRequests,
                'upcoming_events' => $events,
                'top_teachers' => $topTeachers,
                'top_students' => $topStudents,
                'new_admissions' => $newAdmissions,
                'charts' => $this->schoolDashboardCharts($schoolId),
            ],
        ]);
    }

    /**
     * Monthly fee / income charts and admissions breakdown for the school dashboard.
     *
     * @return array{
     *   fee_revenue: list<array{month: string, total_fee: float, collected: float}>,
     *   income_expense: list<array{month: string, income: float, expense: float}>,
     *   admissions_by_class: list<array{label: string, value: int}>,
     *   calendar_events: list<array{id: int, title: string, starts_at: string|null, type: string|null}>
     * }
     */
    private function schoolDashboardCharts(?int $schoolId): array
    {
        $months = collect(range(0, 11))->map(fn ($offset) => now()->subMonths(11 - $offset));

        $feeRevenue = [];
        $incomeExpense = [];

        foreach ($months as $month) {
            $label = $month->format('M');

            $totalFee = (float) Invoice::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('amount');

            $collected = (float) Payment::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('status', 'completed')
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year)
                ->sum('amount');

            $income = (float) Transaction::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('type', 'income')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('credit');

            $expense = (float) Transaction::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('type', 'expense')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('debit');

            $feeRevenue[] = [
                'month' => $label,
                'total_fee' => round($totalFee, 2),
                'collected' => round($collected, 2),
            ];

            $incomeExpense[] = [
                'month' => $label,
                'income' => round($income, 2),
                'expense' => round($expense, 2),
            ];
        }

        $admissionsByClass = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereYear('created_at', now()->year)
            ->with('classModel:id,name')
            ->get(['id', 'class_id', 'class'])
            ->groupBy(function (Student $student) {
                $className = $student->classModel?->name
                    ?? (is_string($student->class) && $student->class !== '' ? $student->class : null);

                return $className ?: 'Unassigned';
            })
            ->map(fn ($group, $label) => [
                'label' => (string) $label,
                'value' => $group->count(),
            ])
            ->sortByDesc('value')
            ->values()
            ->take(8)
            ->values()
            ->all();

        $calendarStart = now()->startOfMonth();
        $calendarEnd = now()->endOfMonth();

        $calendarEvents = SchoolEvent::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', '!=', 'cancelled')
            ->whereBetween('starts_at', [$calendarStart, $calendarEnd])
            ->orderBy('starts_at')
            ->limit(40)
            ->get(['id', 'title', 'starts_at', 'type'])
            ->map(fn (SchoolEvent $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => optional($event->starts_at)->toIso8601String(),
                'type' => $event->type,
            ])
            ->values()
            ->all();

        return [
            'fee_revenue' => $feeRevenue,
            'income_expense' => $incomeExpense,
            'admissions_by_class' => $admissionsByClass,
            'calendar_events' => $calendarEvents,
        ];
    }

    /**
     * EduDash-style LMS dashboard aggregates for school admins.
     */
    public function lmsWidgets(Request $request)
    {
        $this->authorizeModuleAccess($request, capabilities: ['isStaff', 'canManageTeachers']);
        $schoolId = $request->user()?->school_id;

        $lessons = OnlineLesson::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $totalLessons = (clone $lessons)->count();
        $liveLessons = (clone $lessons)->where('lesson_type', 'live')->count();
        $recordedLessons = (clone $lessons)->where('lesson_type', 'recorded')->count();
        $instructors = OnlineLesson::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('teacher_id')
            ->pluck('teacher_id')
            ->unique()
            ->count();

        $activeStudents = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->count();

        $upcoming = OnlineLesson::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '>=', now());
            })
            ->with(['teacher:id,name', 'classModel:id,name'])
            ->orderByRaw('scheduled_at is null')
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn (OnlineLesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'lesson_type' => $lesson->lesson_type,
                'status' => $lesson->status,
                'scheduled_at' => optional($lesson->scheduled_at)->toIso8601String(),
                'teacher_name' => $lesson->teacher?->name,
                'class_name' => $lesson->classModel?->name,
            ]);

        $recent = OnlineLesson::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['teacher:id,name', 'classModel:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (OnlineLesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'lesson_type' => $lesson->lesson_type,
                'status' => $lesson->status,
                'scheduled_at' => optional($lesson->scheduled_at)->toIso8601String(),
                'teacher_name' => $lesson->teacher?->name,
                'class_name' => $lesson->classModel?->name,
            ]);

        $topInstructors = OnlineLesson::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->selectRaw('teacher_id, count(*) as lesson_count')
            ->whereNotNull('teacher_id')
            ->groupBy('teacher_id')
            ->orderByDesc('lesson_count')
            ->limit(5)
            ->get();

        $teachersById = Teacher::query()
            ->whereIn('id', $topInstructors->pluck('teacher_id')->filter()->all() ?: [0])
            ->get(['id', 'name', 'email', 'subject'])
            ->keyBy('id');

        $instructorsList = $topInstructors->map(function ($row) use ($teachersById) {
            $teacher = $teachersById->get($row->teacher_id);

            return [
                'id' => $row->teacher_id,
                'name' => $teacher?->name ?? 'Teacher',
                'email' => $teacher?->email,
                'subject' => $teacher?->subject,
                'lesson_count' => (int) $row->lesson_count,
            ];
        });

        $byType = OnlineLesson::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->selectRaw('lesson_type, count(*) as total')
            ->groupBy('lesson_type')
            ->get()
            ->map(fn ($row) => [
                'label' => ucfirst(str_replace('_', ' ', (string) $row->lesson_type)),
                'value' => (int) $row->total,
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'kpis' => [
                    'total_lessons' => $totalLessons,
                    'live_lessons' => $liveLessons,
                    'recorded_lessons' => $recordedLessons,
                    'instructors' => $instructors,
                    'active_students' => $activeStudents,
                ],
                'upcoming_sessions' => $upcoming,
                'recent_sessions' => $recent,
                'top_instructors' => $instructorsList,
                'sessions_by_type' => $byType,
            ],
        ]);
    }

    /**
     * Admin-only EduDash-style role dashboard previews (aggregate school data).
     */
    public function rolePreview(Request $request, string $role)
    {
        $this->authorizeModuleAccess($request, capabilities: ['canManageTeachers']);
        $schoolId = $request->user()?->school_id;
        $role = strtolower($role);

        abort_unless(in_array($role, ['student', 'teacher', 'parent'], true), 404);

        $students = Student::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $teachers = Teacher::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $today = now()->toDateString();

        $attendanceToday = Attendance::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereDate('date', $today)
            ->get();

        $presentRate = $attendanceToday->count() > 0
            ? round(($attendanceToday->where('status', 'present')->count() / $attendanceToday->count()) * 100, 1)
            : 0;

        $notices = Announcement::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderByDesc('date')
            ->limit(5)
            ->get(['id', 'title', 'message', 'date']);

        $events = SchoolEvent::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('starts_at', '>=', now()->startOfDay())
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->limit(5)
            ->get(['id', 'title', 'starts_at', 'location']);

        $base = [
            'role' => $role,
            'notices' => $notices->map(fn (Announcement $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'message' => \Illuminate\Support\Str::limit((string) $a->message, 100),
                'date' => $a->date?->toDateString(),
            ]),
            'upcoming_events' => $events->map(fn (SchoolEvent $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'starts_at' => optional($e->starts_at)->toIso8601String(),
                'location' => $e->location,
            ]),
        ];

        if ($role === 'student') {
            return response()->json([
                'data' => array_merge($base, [
                    'title' => 'Student dashboard preview',
                    'subtitle' => 'How learners experience the school portal',
                    'kpis' => [
                        ['label' => 'Active students', 'value' => (clone $students)->where('status', 'active')->count()],
                        ['label' => 'Classes', 'value' => ClassModel::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count()],
                        ['label' => 'Attendance today', 'value' => $presentRate.'%'],
                        ['label' => 'Online lessons', 'value' => OnlineLesson::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count()],
                    ],
                    'highlights' => [
                        'View timetable, homework, and exam results from the student portal.',
                        'Attendance and performance summaries update as teachers mark registers and results.',
                    ],
                ]),
            ]);
        }

        if ($role === 'teacher') {
            $lessonPlansDraft = LessonPlan::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('status', 'draft')
                ->count();

            return response()->json([
                'data' => array_merge($base, [
                    'title' => 'Teacher dashboard preview',
                    'subtitle' => 'How teaching staff experience their workspace',
                    'kpis' => [
                        ['label' => 'Active teachers', 'value' => (clone $teachers)->where('status', 'active')->count()],
                        ['label' => 'Online lessons', 'value' => OnlineLesson::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count()],
                        ['label' => 'Draft lesson plans', 'value' => $lessonPlansDraft],
                        ['label' => 'Attendance marked today', 'value' => $attendanceToday->pluck('class_id')->unique()->count()],
                    ],
                    'highlights' => [
                        'Teachers manage classes, lesson plans, homework, and LMS from Teaching.',
                        'Staff attendance and leave stay under HR.',
                    ],
                ]),
            ]);
        }

        $parents = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('role', UserRole::Parent)
            ->count();

        $outstanding = Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', '!=', 'paid')
            ->sum('balance');

        return response()->json([
            'data' => array_merge($base, [
                'title' => 'Parent dashboard preview',
                'subtitle' => 'How guardians experience the parent portal',
                'kpis' => [
                    ['label' => 'Parent accounts', 'value' => $parents],
                    ['label' => 'Linked students', 'value' => (clone $students)->where('status', 'active')->count()],
                    ['label' => 'Outstanding fees', 'value' => round((float) $outstanding, 2)],
                    ['label' => 'Notices', 'value' => $notices->count()],
                ],
                'highlights' => [
                    'Parents see children’s attendance, fees, notices, and school events.',
                    'Use Communications to message guardians directly.',
                ],
            ]),
        ]);
    }

    public function activity(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $schoolId = $request->user()?->school_id;

        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $attendance = Attendance::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date->toDateString());

        $payments = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date->toDateString());

        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->toDateString();
            $attendanceCount = $attendance[$date]->total ?? 0;
            $paymentCount = $payments[$date]->total ?? 0;
            $data[] = [
                'date' => $date,
                'value' => (int) $attendanceCount + (int) $paymentCount,
                'label' => date('M j', strtotime($date)),
            ];
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    public function monthlyStats(Request $request)
    {
        $schoolId = $request->user()?->school_id;
        $months = collect(range(0, 11))->map(fn ($offset) => now()->subMonths(11 - $offset));

        $data = [];

        foreach ($months as $month) {
            $label = $month->format('M');
            $revenue = Payment::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('status', 'completed')
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year)
                ->sum('amount');

            $users = User::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $transactions = Transaction::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $data[] = ['month' => $label, 'value' => (float) $revenue, 'category' => 'Revenue'];
            $data[] = ['month' => $label, 'value' => (int) $users, 'category' => 'Users'];
            $data[] = ['month' => $label, 'value' => (int) $transactions, 'category' => 'Transactions'];
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    public function recentActivity(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        $schoolId = $request->user()?->school_id;

        $payments = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('student:id,full_name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'id' => "payment-{$p->id}",
                'type' => 'payment',
                'action' => $p->status === 'completed' ? 'completed' : $p->status,
                'user' => $p->student?->full_name ?? 'Student',
                'description' => "Payment of {$p->currency} {$p->amount}",
                'timestamp' => $p->created_at?->toIso8601String(),
                'status' => $p->status === 'completed' ? 'success' : 'warning',
            ]);

        $invoices = Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('student:id,full_name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'id' => "invoice-{$i->id}",
                'type' => 'invoice',
                'action' => 'created',
                'user' => $i->student?->full_name ?? 'Student',
                'description' => "Invoice {$i->invoice_number} created",
                'timestamp' => $i->created_at?->toIso8601String(),
                'status' => 'info',
            ]);

        $students = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'id' => "student-{$s->id}",
                'type' => 'student',
                'action' => 'created',
                'user' => $s->full_name,
                'description' => 'Student registered',
                'timestamp' => $s->created_at?->toIso8601String(),
                'status' => 'success',
            ]);

        $activity = $payments
            ->merge($invoices)
            ->merge($students)
            ->sortByDesc('timestamp')
            ->values()
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $activity,
        ]);
    }
}
