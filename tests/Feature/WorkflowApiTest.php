<?php

namespace Tests\Feature;

use App\Models\PurchaseRequisition;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Tests\TestCase;

class WorkflowApiTest extends TestCase
{
    public function test_pending_returns_normalized_workflow_payload(): void
    {
        $auth = $this->createAuthenticatedUser();
        $this->seedPendingWorkflow($auth);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/workflows/pending');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'workflow_name',
                        'workflow_code',
                        'module',
                        'subject_label',
                        'current_step_name',
                        'initiated_by_name',
                        'definition' => ['steps'],
                    ],
                ],
            ]);
    }

    public function test_history_supports_status_and_module_filters(): void
    {
        $auth = $this->createAuthenticatedUser();
        $instance = $this->seedPendingWorkflow($auth);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/workflows/{$instance->id}/reject", ['comments' => 'Budget not available'])
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/workflows/history?status=rejected&module=procurement')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'rejected')
            ->assertJsonPath('data.0.module', 'procurement');
    }

    public function test_reject_requires_comments(): void
    {
        $auth = $this->createAuthenticatedUser();
        $instance = $this->seedPendingWorkflow($auth);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/workflows/{$instance->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['comments']);
    }

    public function test_approve_accepts_optional_comments(): void
    {
        $auth = $this->createAuthenticatedUser();
        $instance = $this->seedPendingWorkflow($auth);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/workflows/{$instance->id}/approve", ['comments' => 'Looks good'])
            ->assertOk()
            ->assertJsonPath('data.current_step_order', 2)
            ->assertJsonPath('data.status', 'pending');
    }

    protected function seedPendingWorkflow(array $auth): WorkflowInstance
    {
        $workflows = app(WorkflowService::class);
        $workflows->ensureDefinitions($auth['school']->id);

        $requisition = PurchaseRequisition::create([
            'school_id' => $auth['school']->id,
            'requested_by' => $auth['user']->id,
            'title' => 'Test requisition',
            'description' => 'Workflow API test',
            'estimated_cost' => 120,
            'status' => 'pending_approval',
        ]);

        return $workflows->start('purchase_request', $requisition, $auth['user'], [
            'title' => $requisition->title,
        ]);
    }
}
