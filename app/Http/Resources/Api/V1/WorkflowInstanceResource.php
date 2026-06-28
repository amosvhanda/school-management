<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowInstance */
class WorkflowInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $definition = $this->relationLoaded('definition') ? $this->definition : null;
        $steps = ($definition && $definition->relationLoaded('steps')) ? $definition->steps : collect();
        $currentStep = $steps->firstWhere('step_order', $this->current_step_order);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'current_step_order' => $this->current_step_order,
            'current_step_name' => $currentStep?->name,
            'total_steps' => $steps->count() ?: null,
            'workflow_code' => $definition?->code,
            'workflow_name' => $definition?->name,
            'module' => $definition?->module,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_label' => $this->resolveSubjectLabel(),
            'metadata' => $this->metadata,
            'initiated_by' => $this->initiated_by,
            'initiated_by_name' => $this->relationLoaded('initiator') ? $this->initiator?->name : null,
            'initiator' => $this->relationLoaded('initiator') && $this->initiator ? [
                'id' => $this->initiator->id,
                'name' => $this->initiator->name,
                'email' => $this->initiator->email,
            ] : null,
            'definition' => $definition ? [
                'id' => $definition->id,
                'code' => $definition->code,
                'name' => $definition->name,
                'module' => $definition->module,
                'steps' => $steps->map(fn ($step) => [
                    'step_order' => $step->step_order,
                    'name' => $step->name,
                    'approver_role' => $step->approver_role,
                ])->values(),
            ] : null,
            'approvals' => $this->relationLoaded('approvals')
                ? $this->approvals->map(fn ($approval) => [
                    'id' => $approval->id,
                    'step_order' => $approval->step_order,
                    'action' => $approval->action,
                    'comments' => $approval->comments,
                    'acted_at' => $approval->acted_at?->toIso8601String(),
                    'approver_name' => $approval->relationLoaded('approver') ? $approval->approver?->name : null,
                ])->values()
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function resolveSubjectLabel(): string
    {
        if ($this->relationLoaded('subject') && $this->subject) {
            $subject = $this->subject;

            return (string) (
                $subject->title
                ?? $subject->name
                ?? $subject->full_name
                ?? $subject->invoice_number
                ?? null
            ) ?: class_basename($this->subject_type).' #'.$this->subject_id;
        }

        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return (string) (
            $metadata['title']
            ?? $metadata['teacher_name']
            ?? $metadata['reason']
            ?? null
        ) ?: class_basename($this->subject_type).' #'.$this->subject_id;
    }
}
