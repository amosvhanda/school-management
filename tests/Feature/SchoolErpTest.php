<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\LibraryBook;
use App\Models\PurchaseRequisition;
use App\Models\Visitor;
use App\Models\WorkflowInstance;
use Tests\TestCase;

class SchoolErpTest extends TestCase
{
    public function test_procurement_requisition_starts_workflow(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/procurement/requisitions', [
                'title' => 'Sports equipment',
                'description' => 'Footballs and nets',
                'submit' => true,
                'items' => [
                    ['description' => 'Football', 'quantity' => 10, 'unit_cost' => 25],
                ],
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('purchase_requisitions', ['title' => 'Sports equipment', 'status' => 'pending_approval']);
        $this->assertDatabaseHas('workflow_instances', ['school_id' => $auth['school']->id, 'status' => 'pending']);
    }

    public function test_workflow_approve_advances_step(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/procurement/requisitions', [
                'title' => 'Lab supplies',
                'submit' => true,
                'items' => [['description' => 'Beakers', 'quantity' => 5, 'unit_cost' => 10]],
            ])->assertCreated();

        $instance = WorkflowInstance::where('school_id', $auth['school']->id)->first();
        $this->assertNotNull($instance);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/workflows/{$instance->id}/approve", ['comments' => 'Approved'])
            ->assertOk();

        $this->assertSame(2, $instance->fresh()->current_step_order);
    }

    public function test_executive_dashboard_returns_kpis(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/enterprise/command-center')
            ->assertOk()
            ->assertJsonStructure(['data' => ['school_health', 'financial_status', 'approval_queue', 'kpi_scorecard']]);
    }

    public function test_asset_register_and_maintenance(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/assets', [
                'name' => 'Projector',
                'category' => 'IT Equipment',
                'purchase_cost' => 500,
            ]);

        $create->assertCreated();
        $assetId = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/assets/{$assetId}/maintenance", [
                'maintenance_date' => now()->toDateString(),
                'description' => 'Bulb replacement',
                'cost' => 50,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('asset_maintenance_logs', ['asset_id' => $assetId]);
    }

    public function test_library_borrow_and_return(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = \App\Models\Student::factory()->create(['school_id' => $auth['school']->id]);

        $book = LibraryBook::create([
            'school_id' => $auth['school']->id,
            'title' => 'Mathematics Grade 7',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $borrow = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/library/loans', [
                'book_id' => $book->id,
                'student_id' => $student->id,
                'due_at' => now()->addDays(14)->toDateString(),
            ]);

        $borrow->assertCreated();
        $loanId = $borrow->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/library/loans/{$loanId}/return")
            ->assertOk();

        $this->assertSame(2, $book->fresh()->available_copies);
    }

    public function test_visitor_check_in_and_out(): void
    {
        $auth = $this->createAuthenticatedUser();

        $checkIn = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/visitors/check-in', [
                'name' => 'John Doe',
                'purpose' => 'Parent meeting',
            ]);

        $checkIn->assertCreated();
        $visitorId = $checkIn->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/visitors/{$visitorId}/check-out")
            ->assertOk();

        $this->assertDatabaseHas('visitors', ['id' => $visitorId, 'status' => 'checked_out']);
    }

    public function test_analytics_insights_endpoint(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/analytics/insights')
            ->assertOk()
            ->assertJsonStructure(['data' => ['class_performance', 'students_at_risk', 'fee_collection_trend']]);
    }
}
