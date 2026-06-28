<?php

namespace App\Services\Platform;

use App\Models\WorkflowDefinition;
use Illuminate\Support\Facades\DB;

class WorkflowBuilderService
{
    public function listDefinitions(int $schoolId)
    {
        return WorkflowDefinition::where('school_id', $schoolId)
            ->with('steps')
            ->orderBy('name')
            ->get();
    }

    public function saveDefinition(int $schoolId, array $data, ?int $id = null): WorkflowDefinition
    {
        return DB::transaction(function () use ($schoolId, $data, $id) {
            $definition = $id
                ? WorkflowDefinition::where('school_id', $schoolId)->findOrFail($id)
                : new WorkflowDefinition(['school_id' => $schoolId]);

            $definition->fill([
                'code' => $data['code'],
                'name' => $data['name'],
                'module' => $data['module'],
                'description' => $data['description'] ?? null,
                'layout' => $data['layout'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
            $definition->save();

            if (isset($data['steps'])) {
                $definition->steps()->delete();
                foreach ($data['steps'] as $i => $step) {
                    $definition->steps()->create([
                        'step_order' => $i + 1,
                        'name' => $step['name'],
                        'approver_role' => $step['approver_role'] ?? null,
                        'approver_user_id' => $step['approver_user_id'] ?? null,
                        'escalation_hours' => $step['escalation_hours'] ?? null,
                        'position' => $step['position'] ?? null,
                    ]);
                }
            }

            return $definition->fresh('steps');
        });
    }
}
