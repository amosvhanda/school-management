<?php

namespace App\Services\Enterprise;

use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinitionStep;
use App\Models\WorkflowDelegation;
use App\Models\WorkflowInstance;

class WorkflowEnterpriseService
{
    public function delegate(int $schoolId, array $data): WorkflowDelegation
    {
        return WorkflowDelegation::create(array_merge($data, ['school_id' => $schoolId]));
    }

    public function canAdvanceParallelStep(WorkflowInstance $instance, WorkflowDefinitionStep $step): bool
    {
        if (($step->approval_mode ?? 'sequential') !== 'parallel') {
            return true;
        }

        $approved = WorkflowApproval::where('instance_id', $instance->id)
            ->where('step_order', $instance->current_step_order)
            ->where('action', 'approved')
            ->count();

        return $approved >= (int) ($step->required_approvals ?? 1);
    }
}
