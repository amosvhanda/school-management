<?php

namespace App\Services\Platform;

use App\Models\OperationsAlert;
use App\Models\WorkflowDefinitionStep;
use App\Models\WorkflowInstance;

class WorkflowEscalationService
{
    public function processEscalations(int $schoolId): int
    {
        $escalated = 0;

        $instances = WorkflowInstance::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->whereNotNull('step_due_at')
            ->where('step_due_at', '<', now())
            ->get();

        foreach ($instances as $instance) {
            $step = WorkflowDefinitionStep::where('definition_id', $instance->definition_id)
                ->where('step_order', $instance->current_step_order)
                ->first();

            $instance->update([
                'escalated_at' => now(),
                'escalation_count' => $instance->escalation_count + 1,
                'step_due_at' => $step?->escalation_hours
                    ? now()->addHours($step->escalation_hours)
                    : null,
            ]);

            OperationsAlert::create([
                'school_id' => $schoolId,
                'severity' => 'warning',
                'category' => 'workflow_escalation',
                'title' => 'Approval overdue',
                'message' => "Workflow #{$instance->id} step {$instance->current_step_order} is overdue.",
                'metadata' => ['workflow_instance_id' => $instance->id],
            ]);

            $escalated++;
        }

        return $escalated;
    }

    public function setStepDueAt(WorkflowInstance $instance): void
    {
        $step = WorkflowDefinitionStep::where('definition_id', $instance->definition_id)
            ->where('step_order', $instance->current_step_order)
            ->first();

        if ($step?->escalation_hours) {
            $instance->update(['step_due_at' => now()->addHours($step->escalation_hours)]);
        }
    }
}
