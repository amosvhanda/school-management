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
            ],
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
