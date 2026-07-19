<?php

namespace App\Services\Platform;

use App\Models\OperationsAlert;
use App\Models\StaffTask;
use App\Models\WorkflowInstance;

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
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

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

        return [
            'timestamp' => now()->toIso8601String(),
            'scope' => $schoolId ? 'school' : 'platform',
            'alerts' => $alerts,
            'metrics' => [
                'pending_workflows' => $pendingWorkflows,
                'overdue_staff_tasks' => $overdueTasks,
                'escalated_workflows' => $escalated,
                'open_staff_tasks' => $openTasks,
                'open_alerts' => $alerts->count(),
            ],
        ];
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
