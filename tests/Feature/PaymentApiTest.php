<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    public function test_get_payments_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
        ]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 400,
            'amount_paid' => 100,
            'balance' => 300,
            'currency' => $auth['school']->currency_default,
        ]);
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'amount' => 100,
            'currency' => $auth['school']->currency_default,
            'method' => 'cash',
        ]);

        $otherSchool = School::factory()->create();
        $otherStudent = Student::factory()->create(['school_id' => $otherSchool->id]);
        $otherInvoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'currency' => $otherSchool->currency_default,
        ]);
        $otherPayment = Payment::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'invoice_id' => $otherInvoice->id,
            'currency' => $otherSchool->currency_default,
            'method' => 'cash',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/payments?all=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $payment->id)
            ->assertJsonPath('data.0.invoice_id', $invoice->id)
            ->assertJsonPath('data.0.student_id', $student->id);

        $this->assertNotContains($otherPayment->id, array_column($response->json('data'), 'id'));
    }

    public function test_create_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'balance' => 500,
        ]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 500,
            'amount_paid' => 0,
            'balance' => 500,
            'status' => 'pending',
            'currency' => $auth['school']->currency_default,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'currency' => $auth['school']->currency_default,
            'method' => 'cash',
            'reference' => 'PAY-REF-001',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'amount', 'method', 'status'],
            ]);

        $paymentId = $response->json('data.id');

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'currency' => $auth['school']->currency_default,
            'method' => 'cash',
            'reference' => 'PAY-REF-001',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('transactions', [
            'payment_id' => $paymentId,
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'type' => 'payment',
            'status' => 'completed',
        ]);
        $this->assertSame('500.00', $invoice->fresh()->amount_paid);
        $this->assertSame('0.00', $invoice->fresh()->balance);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0.0, (float) $student->fresh()->balance);
    }

    public function test_payment_invoice_must_belong_to_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $student->id,
            'currency' => $otherSchool->currency_default,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'currency' => $auth['school']->currency_default,
            'method' => 'cash',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('invoice_id');
    }

    public function test_get_payment_receipt(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'currency' => $auth['school']->currency_default,
        ]);
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'currency' => $auth['school']->currency_default,
            'method' => 'cash',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/payments/{$payment->id}/receipt");

        $response->assertOk()
            ->assertJsonPath('data.payment.id', $payment->id)
            ->assertJsonPath('data.school.id', $auth['school']->id)
            ->assertJsonPath('data.receipt_number', 'RCT-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT));
    }

    public function test_cannot_get_payment_receipt_for_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $student->id,
            'currency' => $otherSchool->currency_default,
        ]);
        $payment = Payment::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'currency' => $otherSchool->currency_default,
            'method' => 'cash',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/payments/{$payment->id}/receipt")
            ->assertNotFound();
    }

    public function test_reverse_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'balance' => 0,
        ]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 500,
            'amount_paid' => 500,
            'balance' => 0,
            'status' => 'paid',
            'currency' => $auth['school']->currency_default,
            'due_date' => now()->addMonth()->toDateString(),
        ]);
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'currency' => $auth['school']->currency_default,
            'method' => 'cash',
            'status' => 'completed',
            'notes' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/payments/{$payment->id}/reverse", [
            'reason' => 'Test reversal',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonPath('data.status', 'reversed');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'reversed',
            'notes' => 'Reversal: Test reversal',
        ]);
        $this->assertDatabaseHas('transactions', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'type' => 'reversal',
            'status' => 'completed',
        ]);
        $this->assertSame('0.00', $invoice->fresh()->amount_paid);
        $this->assertSame('500.00', $invoice->fresh()->balance);
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertSame(500.0, (float) $student->fresh()->balance);
    }

    public function test_delete_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'pending',
            'method' => 'cash',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Payment deleted successfully');

        $this->assertSoftDeleted('payments', [
            'id' => $payment->id,
        ]);
    }

    public function test_cannot_delete_completed_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'completed',
            'method' => 'cash',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Completed payments cannot be deleted. Use reverse instead.');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
    }
}
