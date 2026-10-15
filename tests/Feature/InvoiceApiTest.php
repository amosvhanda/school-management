<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\School;
use App\Models\Student;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    public function test_get_invoices_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'description' => 'Own school invoice',
            'amount' => 750,
            'amount_paid' => 0,
            'balance' => 750,
            'status' => 'pending',
            'due_date' => now()->subDay()->toDateString(),
            'currency' => $auth['school']->currency_default,
        ]);

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);
        $otherInvoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'description' => 'Other school invoice',
            'currency' => $otherSchool->currency_default,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/invoices?all=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $invoice->id)
            ->assertJsonPath('data.0.status', 'overdue');

        $this->assertNotContains($otherInvoice->id, array_column($response->json('data'), 'id'));
        $this->assertSame('overdue', $invoice->fresh()->status);
    }

    public function test_create_invoice(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'balance' => 0,
        ]);
        $feeStructure = FeeStructure::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/invoices', [
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'description' => 'Tuition fee for Term 1',
            'amount' => 1000.00,
            'due_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'amount', 'due_date', 'balance', 'status'],
                'message',
            ]);

        $invoiceId = $response->json('data.id');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'description' => 'Tuition fee for Term 1',
            'amount' => 1000,
            'balance' => 1000,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('transactions', [
            'invoice_id' => $invoiceId,
            'student_id' => $student->id,
            'type' => 'fee_applied',
            'status' => 'completed',
        ]);
        $this->assertSame(1000.0, (float) $student->fresh()->balance);
    }

    public function test_get_invoice_by_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'currency' => $auth['school']->currency_default,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.school_id', $auth['school']->id)
            ->assertJsonPath('data.student.id', $student->id);
    }

    public function test_cannot_get_invoice_for_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $student->id,
            'currency' => $otherSchool->currency_default,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/invoices/{$invoice->id}")
            ->assertNotFound();
    }

    public function test_update_invoice(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 1000,
            'amount_paid' => 200,
            'balance' => 800,
            'status' => 'partial',
            'currency' => $auth['school']->currency_default,
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/invoices/{$invoice->id}", [
            'amount' => 1200.00,
            'due_date' => now()->addMonths(2)->format('Y-m-d'),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.amount', '1200.00')
            ->assertJsonPath('data.balance', '1000.00')
            ->assertJsonPath('data.status', 'partial');

        $this->assertSame('1200.00', $invoice->fresh()->amount);
        $this->assertSame('1000.00', $invoice->fresh()->balance);
        $this->assertSame('partial', $invoice->fresh()->status);
    }

    public function test_cannot_update_paid_invoice(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'status' => 'paid',
            'amount' => 1000,
            'amount_paid' => 1000,
            'balance' => 0,
            'currency' => $auth['school']->currency_default,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/invoices/{$invoice->id}", [
            'amount' => 1200.00,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Paid invoices cannot be edited.');
    }

    public function test_cannot_update_invoice_for_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $student->id,
            'currency' => $otherSchool->currency_default,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/invoices/{$invoice->id}", [
            'amount' => 1200.00,
        ])->assertNotFound();
    }

    public function test_print_invoice_returns_document_payload(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'description' => 'Term fees',
            'amount' => 500,
            'amount_paid' => 0,
            'balance' => 500,
            'status' => 'pending',
            'currency' => $auth['school']->currency_default,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/invoices/{$invoice->id}/print");

        $response->assertOk()
            ->assertJsonPath('data.invoice.id', $invoice->id)
            ->assertJsonPath('data.invoice.description', 'Term fees')
            ->assertJsonPath('data.school.id', $auth['school']->id)
            ->assertJsonStructure([
                'data' => [
                    'document_number',
                    'issued_at',
                    'invoice' => ['id', 'amount', 'balance', 'student'],
                    'school',
                ],
            ]);
    }
}
