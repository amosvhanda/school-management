<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Attendance;
use App\Models\DisciplinaryRecord;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Vehicle;
use App\Models\WorkflowInstance;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $today = now()->toDateString();

        $students = Student::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $attendanceToday = Attendance::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereDate('date', $today);
        $totalToday = (clone $attendanceToday)->count();
        $presentToday = (clone $attendanceToday)->where('status', 'present')->count();
        $attendanceRate = $totalToday > 0 ? round(($presentToday / $totalToday) * 100, 1) : 0;

        $outstanding = Invoice::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', '!=', 'paid')
            ->sum('balance');
        $collectedMonth = Payment::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->whereMonth('date', now()->month)
            ->sum('amount');

        $pendingWorkflows = WorkflowInstance::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'pending')
            ->count();

        $lowStock = InventoryItem::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->count();

        $vehiclesDown = Vehicle::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', '!=', 'active')
            ->count();

        $atRiskStudents = (clone $students)->where('status', 'active')->where('balance', '>', 0)->count();

        return response()->json([
            'data' => [
                'students' => [
                    'total' => (clone $students)->count(),
                    'active' => (clone $students)->where('status', 'active')->count(),
                    'attendance_rate_today' => $attendanceRate,
                    'fee_arrears_count' => $atRiskStudents,
                ],
                'finance' => [
                    'outstanding_fees' => round((float) $outstanding, 2),
                    'collected_this_month' => round((float) $collectedMonth, 2),
                ],
                'hr' => [
                    'active_teachers' => Teacher::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('status', 'active')->count(),
                    'pending_leave_requests' => LeaveRequest::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('status', 'pending')->count(),
                ],
                'operations' => [
                    'assets_total' => Asset::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('status', 'active')->count(),
                    'vehicles_inactive' => $vehiclesDown,
                    'inventory_low_stock' => $lowStock,
                ],
                'alerts' => [
                    'overdue_approvals' => $pendingWorkflows,
                    'open_discipline_cases' => DisciplinaryRecord::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('created_at', '>=', now()->subDays(30))->count(),
                    'students_with_arrears' => $atRiskStudents,
                ],
            ],
        ]);
    }
}
