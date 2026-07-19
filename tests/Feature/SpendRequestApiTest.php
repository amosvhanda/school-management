<?php

namespace Tests\Feature;

use App\Models\PurchaseRequisition;
use App\Models\Transaction;
use App\Models\WorkflowInstance;
use App\Services\WorkflowService;
use Tests\TestCase;

class SpendRequestApiTest extends TestCase
{
    public function test_spend_request_requires_approval_before_payment(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/procurement/requisitions', [
            'title' => 'Classroom stationery',
            'spend_type' => 'procurement',
            'submit' => true,
            'items' => [
                ['description' => 'Exercise books', 'quantity' => 10, 'unit_cost' => 2.5],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', 'pending_approval');

        $id = $create->json('data.id');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/procurement/requisitions/{$id}/disburse", [
            'payment_method' => 'cash',
            'amount_paid' => 25,
        ])->assertStatus(422);
    }

    public function test_approved_spend_request_can_be_disbursed_to_ledger(): void
    {
        $auth = $this->createAuthenticatedUser('admin');

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/procurement/requisitions', [
            'title' => 'Printer ink',
            'spend_type' => 'procurement',
            'submit' => true,
            'items' => [
                ['description' => 'Ink cartridges', 'quantity' => 2, 'unit_cost' => 40],
            ],
        ])->assertCreated();

        $requisitionId = $create->json('data.id');
        $instanceId = $create->json('data.workflow_instance_id');
        $this->assertNotNull($instanceId);

        $workflows = app(WorkflowService::class);
        $instance = WorkflowInstance::with('definition.steps')->findOrFail($instanceId);
        $maxStep = (int) $instance->definition->steps()->max('step_order');

        for ($i = 0; $i < $maxStep; $i++) {
            $workflows->approve($instance->fresh(['definition.steps']), $auth['user']);
        }

        $this->assertDatabaseHas('purchase_requisitions', [
            'id' => $requisitionId,
            'status' => 'approved',
        ]);

        $pay = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/procurement/requisitions/{$requisitionId}/disburse", [
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'PO-100',
            'amount_paid' => 80,
        ]);

        $pay->assertOk()
            ->assertJsonPath('data.status', 'disbursed');

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'category' => 'procurement',
            'debit' => 80,
            'reference' => 'PO-100',
            'status' => 'completed',
        ]);

        $this->assertSame(1, Transaction::query()
            ->where('category', 'procurement')
            ->where('debit', 80)
            ->count());

        $this->assertNotNull(PurchaseRequisition::find($requisitionId)?->transaction_id);
    }

    public function test_goods_receipt_requires_approval_or_payment(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/procurement/requisitions', [
            'title' => 'Desks',
            'spend_type' => 'procurement',
            'submit' => true,
            'items' => [
                ['description' => 'Desk', 'quantity' => 1, 'unit_cost' => 100],
            ],
        ])->assertCreated();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/procurement/goods-receipts', [
            'requisition_id' => $create->json('data.id'),
            'received_date' => now()->toDateString(),
        ])->assertStatus(422);
    }
}
