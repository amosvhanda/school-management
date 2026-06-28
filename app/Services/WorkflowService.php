<?php

namespace App\Services;

use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionStep;
use App\Models\WorkflowInstance;
use App\Models\User;
use App\Services\Enterprise\WorkflowEnterpriseService;
use App\Services\Platform\WorkflowEscalationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkflowService
{
    public function __construct(
        private AuditService $audit,
        private ?WorkflowEscalationService $escalation = null,
        private ?WorkflowEnterpriseService $workflowEnterprise = null,
    ) {}

    public function ensureDefinitions(int $schoolId): void
    {
        $defaults = [
            'purchase_request' => [
                'name' => 'Purchase Request',
                'module' => 'procurement',
                'steps' => [
                    ['name' => 'Department Head', 'approver_role' => 'admin'],
                    ['name' => 'Finance Verification', 'approver_role' => 'finance'],
                    ['name' => 'Headmaster Approval', 'approver_role' => 'admin'],
                    ['name' => 'Procurement', 'approver_role' => 'accounts'],
                ],
            ],
            'leave_request' => [
                'name' => 'Leave Request',
                'module' => 'hr',
                'steps' => [
                    ['name' => 'Supervisor', 'approver_role' => 'admin'],
                    ['name' => 'HR Approval', 'approver_role' => 'admin'],
                ],
            ],
            'fee_waiver' => [
                'name' => 'Fee Waiver',
                'module' => 'finance',
                'steps' => [
                    ['name' => 'Finance Review', 'approver_role' => 'finance'],
                    ['name' => 'Headmaster Approval', 'approver_role' => 'admin'],
                ],
            ],
            'result_publication' => [
                'name' => 'Result Publication',
                'module' => 'examination',
                'steps' => [
                    ['name' => 'Exam Officer', 'approver_role' => 'examination_officer'],
                    ['name' => 'Headmaster Approval', 'approver_role' => 'admin'],
                ],
            ],
            'asset_disposal' => [
                'name' => 'Asset Disposal',
                'module' => 'assets',
                'steps' => [
                    ['name' => 'Department Head', 'approver_role' => 'admin'],
                    ['name' => 'Finance Review', 'approver_role' => 'finance'],
                    ['name' => 'Headmaster Approval', 'approver_role' => 'admin'],
                ],
            ],
        ];

        foreach ($defaults as $code => $config) {
            $definition = WorkflowDefinition::firstOrCreate(
                ['school_id' => $schoolId, 'code' => $code],
                ['name' => $config['name'], 'module' => $config['module'], 'is_active' => true],
            );

            if ($definition->steps()->count() === 0) {
                foreach ($config['steps'] as $i => $step) {
                    $definition->steps()->create([
                        'step_order' => $i + 1,
                        'name' => $step['name'],
                        'approver_role' => $step['approver_role'],
                    ]);
                }
            }
        }
    }

    public function start(string $code, Model $subject, User $initiator, array $metadata = []): WorkflowInstance
    {
        $this->ensureDefinitions($initiator->school_id);

        $definition = WorkflowDefinition::where('school_id', $initiator->school_id)
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($definition, $subject, $initiator, $metadata) {
            $instance = WorkflowInstance::create([
                'school_id' => $initiator->school_id,
                'definition_id' => $definition->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'status' => 'pending',
                'current_step_order' => 1,
                'initiated_by' => $initiator->id,
                'metadata' => $metadata,
            ]);

            $this->audit->log(
                module: 'workflow',
                action: 'started',
                auditable: $instance,
                description: "Workflow {$definition->code} started",
                metadata: ['definition' => $definition->code, 'subject_id' => $subject->getKey()],
            );

            $this->escalation?->setStepDueAt($instance->fresh());

            return $instance;
        });
    }

    public function approve(WorkflowInstance $instance, User $approver, ?string $comments = null): WorkflowInstance
    {
        $this->assertCanActOnStep($instance, $approver);

        return DB::transaction(function () use ($instance, $approver, $comments) {
            WorkflowApproval::create([
                'instance_id' => $instance->id,
                'step_order' => $instance->current_step_order,
                'approver_id' => $approver->id,
                'action' => 'approved',
                'comments' => $comments,
            ]);

            $maxStep = $instance->definition->steps()->max('step_order');
            $currentStep = $instance->definition->steps()
                ->where('step_order', $instance->current_step_order)
                ->first();

            $canAdvance = $this->workflowEnterprise?->canAdvanceParallelStep($instance->fresh(), $currentStep) ?? true;

            if ($instance->current_step_order >= $maxStep && $canAdvance) {
                $instance->update(['status' => 'approved', 'completed_at' => now()]);
            } elseif ($canAdvance) {
                $instance->update(['current_step_order' => $instance->current_step_order + 1]);
                $this->escalation?->setStepDueAt($instance->fresh());
            }

            $this->audit->log(
                module: 'workflow',
                action: 'approved',
                auditable: $instance->fresh(),
                description: "Workflow step {$instance->current_step_order} approved",
                metadata: ['comments' => $comments],
            );

            return $instance->fresh(['definition.steps', 'approvals']);
        });
    }

    public function reject(WorkflowInstance $instance, User $approver, ?string $comments = null): WorkflowInstance
    {
        $this->assertCanActOnStep($instance, $approver);

        WorkflowApproval::create([
            'instance_id' => $instance->id,
            'step_order' => $instance->current_step_order,
            'approver_id' => $approver->id,
            'action' => 'rejected',
            'comments' => $comments,
        ]);

        $instance->update(['status' => 'rejected', 'completed_at' => now()]);

        $this->audit->log(
            module: 'workflow',
            action: 'rejected',
            auditable: $instance,
            description: 'Workflow rejected',
            metadata: ['comments' => $comments],
        );

        return $instance->fresh(['definition.steps', 'approvals']);
    }

    public function pendingForUser(User $user)
    {
        $this->ensureDefinitions($user->school_id);
        $role = $user->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user->role;
        if ($role === 'school_admin') {
            $role = 'admin';
        }

        return WorkflowInstance::query()
            ->where('school_id', $user->school_id)
            ->where('status', 'pending')
            ->with(['definition.steps', 'initiator:id,name,email'])
            ->whereHas('definition.steps', function ($q) use ($user, $role) {
                $q->whereColumn('workflow_definition_steps.step_order', 'workflow_instances.current_step_order')
                    ->where(function ($inner) use ($user, $role) {
                        $inner->where('approver_user_id', $user->id)
                            ->orWhere('approver_role', $role);
                    });
            })
            ->orderByDesc('created_at')
            ->get();
    }

    protected function assertCanActOnStep(WorkflowInstance $instance, User $approver): void
    {
        if ($instance->status !== 'pending') {
            throw new AccessDeniedHttpException('Workflow is not pending approval.');
        }

        if ($instance->school_id !== $approver->school_id) {
            throw new AccessDeniedHttpException('Cross-school workflow action denied.');
        }

        $step = $instance->definition->steps()
            ->where('step_order', $instance->current_step_order)
            ->first();

        if (! $step) {
            throw new AccessDeniedHttpException('Invalid workflow step.');
        }

        $role = $approver->role instanceof \App\Enums\UserRole ? $approver->role->value : (string) $approver->role;
        if ($role === 'school_admin') {
            $role = 'admin';
        }

        $allowed = ($step->approver_user_id && $step->approver_user_id === $approver->id)
            || ($step->approver_role && $step->approver_role === $role)
            || in_array($role, ['admin', 'super_admin'], true);

        if (! $allowed) {
            throw new AccessDeniedHttpException('You are not authorized for this approval step.');
        }
    }
}
