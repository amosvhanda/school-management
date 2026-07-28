<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewayConfig;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentDocumentsAndOnlinePaymentsTest extends TestCase
{
    public function test_staff_can_list_and_delete_student_documents(): void
    {
        Storage::fake('public');

        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/students/{$student->id}/documents", [
                'documents' => [
                    UploadedFile::fake()->create('id-scan.pdf', 100, 'application/pdf'),
                ],
                'type' => 'identity',
            ])
            ->assertCreated();

        $list = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/documents")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $documentId = $list->json('data.0.id');
        $this->assertNotEmpty($list->json('data.0.url'));

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/students/{$student->id}/documents/{$documentId}")
            ->assertOk();

        $this->assertDatabaseMissing('student_documents', ['id' => $documentId]);
    }

    public function test_paynow_sandbox_checkout_records_ledger_payment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 100,
            'balance' => 100,
            'status' => 'pending',
            'currency' => 'USD',
        ]);

        PaymentGatewayConfig::create([
            'school_id' => $auth['school']->id,
            'provider' => 'paynow',
            'credentials' => ['mode' => 'sandbox'],
            'is_active' => true,
            'supports_mobile_money' => true,
        ]);

        $initiate = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/platform/payments/initiate', [
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => 40,
                'payment_method' => 'mobile_money',
                'provider' => 'paynow',
            ])
            ->assertCreated();

        $reference = $initiate->json('data.transaction.internal_reference');
        $checkoutUrl = $initiate->json('data.checkout_url');
        $this->assertNotEmpty($reference);
        $this->assertNotEmpty($checkoutUrl);

        $this->getJson('/api/v1/webhooks/payments/paynow/sandbox-checkout?ref='.$reference)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'reference' => $reference,
            'status' => 'completed',
        ]);

        $invoice->refresh();
        $this->assertEquals(60.0, (float) $invoice->balance);
        $this->assertEquals(1, Payment::where('invoice_id', $invoice->id)->count());

        // Idempotent webhook
        $this->postJson('/api/v1/webhooks/payments/paynow', [
            'reference' => $reference,
            'status' => 'paid',
        ])->assertOk();

        $this->assertEquals(1, Payment::where('invoice_id', $invoice->id)->count());
    }
}
