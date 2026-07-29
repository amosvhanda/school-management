<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\PurchaseRequisition;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private function authorizeAnalytics(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['reports.view'],
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAnalytics($request);

        $schoolId = $request->user()->school_id;

        $classPerformance = Grade::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->select('class_id', DB::raw('AVG(CASE WHEN total > 0 THEN (score/total)*100 ELSE 0 END) as avg_percent'))
            ->groupBy('class_id')
            ->with('classModel:id,name')
            ->orderByDesc('avg_percent')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'class_id' => $row->class_id,
                'class_name' => $row->classModel?->name,
                'average_percent' => round((float) $row->avg_percent, 1),
            ]);

        $studentsAtRisk = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('balance', '>', 0)
                    ->orWhereIn('id', function ($sub) {
                        $sub->select('student_id')
                            ->from('attendance')
                            ->where('status', 'absent')
                            ->whereDate('date', '>=', now()->subDays(14))
                            ->groupBy('student_id')
                            ->havingRaw('COUNT(*) >= 5');
                    });
            })
            ->limit(20)
            ->get(['id', 'full_name', 'student_number', 'balance', 'class_id']);

        $feeTrend = Payment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'completed')
            ->where('date', '>=', now()->subMonths(6)->startOfMonth())
            ->get()
            ->groupBy(fn (Payment $p) => $p->date?->format('Y-m'))
            ->map(fn ($rows, $month) => ['month' => $month, 'total' => round($rows->sum('amount'), 2)])
            ->values();

        $departmentSpend = PurchaseRequisition::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', '!=', 'draft')
            ->select('department_id', DB::raw('SUM(estimated_cost) as total'))
            ->groupBy('department_id')
            ->get();

        $assetsNeedingReplacement = Asset::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->where('purchase_date', '<=', now()->subYears(5))
            ->count();

        $examAverages = ExamResult::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->select('exam_id', DB::raw('AVG(CASE WHEN total_marks > 0 THEN (marks_obtained/total_marks)*100 ELSE 0 END) as avg_percent'))
            ->groupBy('exam_id')
            ->with('exam:id,name')
            ->orderByDesc('avg_percent')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'class_performance' => $classPerformance,
                'students_at_risk' => $studentsAtRisk,
                'fee_collection_trend' => $feeTrend,
                'department_spend' => $departmentSpend,
                'assets_needing_replacement' => $assetsNeedingReplacement,
                'top_exam_performance' => $examAverages,
            ],
        ]);
    }
}
