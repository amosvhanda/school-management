<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Student;

class PaymentApiTest extends TestCase
{
    public function test_get_payments_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/payments');

        $response->assertStatus(200);
    }

    public function test_create_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'currency' => $auth['school']->currency_default,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'currency' => $auth['school']->currency_default,
            'method' => 'cash',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'amount', 'method'],
            ]);
    }

    public function test_payment_invoice_must_belong_to_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = \App\Models\School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $otherSchool->id,
            'student_id' => $student->id,
            'currency' => $otherSchool->currency_default,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
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
        $payment = Payment::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/payments/{$payment->id}/receipt");

        $response->assertStatus(200);
    }

    public function test_reverse_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $payment = Payment::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson("/api/v1/payments/{$payment->id}/reverse", [
            'reason' => 'Test reversal',
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(200);
    }

    public function test_cannot_delete_completed_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'completed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(422);
    }
}
