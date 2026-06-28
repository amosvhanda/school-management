<?php

namespace App\Services\Platform;

use App\Models\OperationsAlert;
use App\Models\StaffTask;
use App\Models\WorkflowInstance;

class OperationsDashboardService
{
    public function liveFeed(int $schoolId): array
    {
        $alerts = OperationsAlert::where('school_id', $schoolId)
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $pendingWorkflows = WorkflowInstance::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->count();

        $overdueTasks = StaffTask::where('school_id', $schoolId)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $escalated = WorkflowInstance::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->whereNotNull('escalated_at')
            ->count();

        return [
            'timestamp' => now()->toIso8601String(),
            'alerts' => $alerts,
            'metrics' => [
                'pending_workflows' => $pendingWorkflows,
                'overdue_staff_tasks' => $overdueTasks,
                'escalated_workflows' => $escalated,
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
