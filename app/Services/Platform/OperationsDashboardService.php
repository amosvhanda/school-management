<?php

namespace App\Services\Platform;

use App\Models\OperationsAlert;
use App\Models\School;
use App\Models\StaffTask;
use App\Models\WorkflowInstance;
use Illuminate\Support\Facades\DB;

class OperationsDashboardService
{
    /**
     * @param  int|null  $schoolId  Null = platform-wide (super admin)
     */
    public function liveFeed(?int $schoolId = null): array
    {
        $alerts = OperationsAlert::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNull('resolved_at')
            ->with('school:id,name,code')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (OperationsAlert $alert) => [
                'id' => $alert->id,
                'school_id' => $alert->school_id,
                'school_name' => $alert->school?->name,
                'school_code' => $alert->school?->code,
                'severity' => $alert->severity,
                'category' => $alert->category,
                'title' => $alert->title,
                'message' => $alert->message,
                'metadata' => $alert->metadata,
                'created_at' => $alert->created_at?->toIso8601String(),
            ])
            ->all();

        $pendingWorkflows = WorkflowInstance::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'pending')
            ->count();

        $overdueTasks = StaffTask::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $escalated = WorkflowInstance::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'pending')
            ->whereNotNull('escalated_at')
            ->count();

        $openTasks = StaffTask::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', '!=', 'completed')
            ->count();

        $payload = [
            'timestamp' => now()->toIso8601String(),
            'scope' => $schoolId ? 'school' : 'platform',
            'alerts' => $alerts,
            'metrics' => [
                'pending_workflows' => $pendingWorkflows,
                'overdue_staff_tasks' => $overdueTasks,
                'escalated_workflows' => $escalated,
                'open_staff_tasks' => $openTasks,
                'open_alerts' => count($alerts),
                'schools_total' => $schoolId ? 1 : School::count(),
            ],
        ];

        if (! $schoolId) {
            $payload['schools'] = $this->schoolBreakdown();
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function schoolBreakdown(): array
    {
        $pendingBySchool = WorkflowInstance::query()
            ->select('school_id', DB::raw('count(*) as total'))
            ->where('status', 'pending')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');

        $overdueBySchool = StaffTask::query()
            ->select('school_id', DB::raw('count(*) as total'))
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->toDateString())
            ->groupBy('school_id')
            ->pluck('total', 'school_id');

        $alertsBySchool = OperationsAlert::query()
            ->select('school_id', DB::raw('count(*) as total'))
            ->whereNull('resolved_at')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');

        return School::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'license_status', 'status'])
            ->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
                'status' => $school->status,
                'license_status' => $school->license_status,
                'pending_workflows' => (int) ($pendingBySchool[$school->id] ?? 0),
                'overdue_staff_tasks' => (int) ($overdueBySchool[$school->id] ?? 0),
                'open_alerts' => (int) ($alertsBySchool[$school->id] ?? 0),
            ])
            ->all();
    }

    public function raiseAlert(int $schoolId, string $severity, string $category, string $title, string $message, ?array $metadata = null): OperationsAlert
    {
        return OperationsAlert::create([
            'school_id' => $schoolId,
            'severity' => $severity,
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
}
