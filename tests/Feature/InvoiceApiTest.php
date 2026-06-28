<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\FeeStructure;

class InvoiceApiTest extends TestCase
{
    public function test_get_invoices_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/invoices');

        $response->assertStatus(200);
    }

    public function test_create_invoice(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/invoices', [
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'description' => 'Tuition fee for Term 1',
            'amount' => 1000.00,
            'currency' => 'USD',
            'due_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'amount', 'due_date'],
                'message',
            ]);
    }

    public function test_update_invoice(): void
    {
        $auth = $this->createAuthenticatedUser();
        $invoice = Invoice::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/v1/invoices/{$invoice->id}", [
            'amount' => 1200.00,
            'due_date' => now()->addMonths(2)->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
    }
}
