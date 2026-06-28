<?php

namespace App\Services\Enterprise;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\ExamResult;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Student;
use App\Models\WorkflowInstance;
use App\Services\Platform\PredictiveAnalyticsService;

class CommandCenterService
{
    public function __construct(
        private StudentIntelligenceService $intelligence,
        private FinanceEnterpriseService $finance,
        private PredictiveAnalyticsService $predictive,
    ) {}

    public function dashboard(int $schoolId): array
    {
        $students = Student::where('school_id', $schoolId);
        $active = (clone $students)->where('status', 'active');
        $examResults = ExamResult::where('school_id', $schoolId)->get();
        $avgPerformance = $examResults->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0) ?? 0;

        $bySubject = $examResults->groupBy('subject_id')->map(function ($rows) {
            $avg = $rows->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0);

            return ['subject_id' => $rows->first()->subject_id, 'average_percent' => round($avg, 1)];
        })->values();

        $outstanding = Invoice::where('school_id', $schoolId)->where('status', '!=', 'paid')->sum('balance');
        $collected = Payment::where('school_id', $schoolId)->whereMonth('date', now()->month)->sum('amount');

        return [
            'timestamp' => now()->toIso8601String(),
            'school_health' => [
                'active_students' => $active->count(),
                'attendance_anomalies' => $this->attendanceAnomalies($schoolId),
                'average_performance' => round($avgPerformance, 1),
            ],
            'financial_status' => [
                'outstanding_fees' => round((float) $outstanding, 2),
                'collected_this_month' => round((float) $collected, 2),
                'cashflow_forecast' => $this->finance->cashflowForecast($schoolId),
            ],
            'academic_heatmap' => $bySubject,
            'approval_queue' => WorkflowInstance::where('school_id', $schoolId)
                ->where('status', 'pending')
                ->with('definition:id,name,code')
                ->limit(20)
                ->get(['id', 'definition_id', 'status', 'current_step_order', 'step_due_at', 'escalated_at']),
            'risk_alerts' => $this->intelligence->earlyWarnings($schoolId)->take(15)->values(),
            'department_comparison' => $this->departmentKpis($schoolId),
            'kpi_scorecard' => [
                'enrollment' => (clone $students)->count(),
                'fee_collection_rate' => $outstanding > 0
                    ? round((($collected / ($collected + $outstanding)) * 100), 1)
                    : 100,
                'pending_leave' => LeaveRequest::where('school_id', $schoolId)->where('status', 'pending')->count(),
            ],
        ];
    }

    protected function attendanceAnomalies(int $schoolId): int
    {
        return Attendance::where('school_id', $schoolId)
            ->where('date', '>=', now()->subDays(7))
            ->where('status', 'absent')
            ->select('student_id')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= 3')
            ->get()
            ->count();
    }

    protected function departmentKpis(int $schoolId): array
    {
        return Department::where('school_id', $schoolId)
            ->withCount('teachers')
            ->get()
            ->map(fn ($d) => [
                'department' => $d->name,
                'teachers' => $d->teachers_count,
            ])
            ->all();
    }
}
