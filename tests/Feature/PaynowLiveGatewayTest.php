<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PaymentGatewayConfig;
use App\Models\Student;
use App\Services\Platform\PaynowGatewayClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaynowLiveGatewayTest extends TestCase
{
    public function test_live_paynow_web_initiate_returns_checkout_url(): void
    {
        $integrationKey = 'live-integration-key';
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 75,
            'balance' => 75,
            'status' => 'pending',
            'currency' => 'USD',
        ]);

        PaymentGatewayConfig::create([
            'school_id' => $auth['school']->id,
            'provider' => 'paynow',
            'credentials' => [
                'mode' => 'live',
                'integration_id' => '12345',
                'integration_key' => $integrationKey,
            ],
            'is_active' => true,
            'supports_mobile_money' => true,
        ]);

        $initiateUrl = config('services.paynow.initiate_url');
        Http::fake([
            $initiateUrl => function ($request) use ($integrationKey) {
                $fields = [
                    'status' => 'Ok',
                    'browserurl' => 'https://www.paynow.co.zw/Payment/ConfirmPayment/999',
                    'pollurl' => 'https://www.paynow.co.zw/Interface/CheckPayment/?guid=abc-123',
                ];
                $fields['hash'] = $this->paynowHash($fields, $integrationKey);

                return Http::response(http_build_query($fields), 200, [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]);
            },
        ]);

        $initiate = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/platform/payments/initiate', [
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => 25,
                'payment_method' => 'card',
                'provider' => 'paynow',
            ])
            ->assertCreated();

        $reference = $initiate->json('data.transaction.internal_reference');
        $this->assertSame(
            'https://www.paynow.co.zw/Payment/ConfirmPayment/999',
            $initiate->json('data.checkout_url')
        );
        $this->assertNotEmpty($reference);

        Http::assertSent(function ($request) use ($initiateUrl) {
            return $request->url() === $initiateUrl
                && $request['status'] === 'Message'
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
        });
    }

    public function test_live_paynow_webhook_with_valid_hash_records_payment(): void
    {
        $integrationKey = 'live-integration-key';
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 50,
            'balance' => 50,
            'status' => 'pending',
            'currency' => 'USD',
        ]);

        PaymentGatewayConfig::create([
            'school_id' => $auth['school']->id,
            'provider' => 'paynow',
            'credentials' => [
                'mode' => 'live',
                'integration_id' => '12345',
                'integration_key' => $integrationKey,
            ],
            'is_active' => true,
            'supports_mobile_money' => true,
        ]);

        Http::fake([
            config('services.paynow.initiate_url') => function () use ($integrationKey) {
                $fields = [
                    'status' => 'Ok',
                    'browserurl' => 'https://www.paynow.co.zw/Payment/ConfirmPayment/999',
                    'pollurl' => 'https://www.paynow.co.zw/Interface/CheckPayment/?guid=abc-123',
                ];
                $fields['hash'] = $this->paynowHash($fields, $integrationKey);

                return Http::response(http_build_query($fields), 200);
            },
        ]);

        $reference = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/platform/payments/initiate', [
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => 50,
                'payment_method' => 'card',
                'provider' => 'paynow',
            ])
            ->assertCreated()
            ->json('data.transaction.internal_reference');

        $webhookFields = [
            'reference' => $reference,
            'amount' => '50.00',
            'paynowreference' => 'PN-123456',
            'status' => 'Paid',
            'pollurl' => 'https://www.paynow.co.zw/Interface/CheckPayment/?guid=abc-123',
        ];
        $webhookFields['hash'] = $this->paynowHash($webhookFields, $integrationKey);

        $this->postJson('/api/v1/webhooks/payments/paynow', $webhookFields)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'reference' => $reference,
            'status' => 'completed',
        ]);

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance);
    }

    public function test_live_paynow_mobile_initiate_returns_instructions(): void
    {
        $integrationKey = 'live-integration-key';
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 30,
            'balance' => 30,
            'status' => 'pending',
            'currency' => 'USD',
        ]);

        PaymentGatewayConfig::create([
            'school_id' => $auth['school']->id,
            'provider' => 'paynow',
            'credentials' => [
                'mode' => 'live',
                'integration_id' => '12345',
                'integration_key' => $integrationKey,
            ],
            'is_active' => true,
            'supports_mobile_money' => true,
        ]);

        $remoteUrl = config('services.paynow.remote_url');
        Http::fake([
            $remoteUrl => function () use ($integrationKey) {
                $fields = [
                    'status' => 'Ok',
                    'pollurl' => 'https://www.paynow.co.zw/Interface/CheckPayment/?guid=mobile-123',
                    'instructions' => 'Please approve the Ecocash prompt on your phone.',
                ];
                $fields['hash'] = $this->paynowHash($fields, $integrationKey);

                return Http::response(http_build_query($fields), 200);
            },
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/platform/payments/initiate', [
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => 30,
                'payment_method' => 'mobile_money',
                'provider' => 'paynow',
                'phone' => '0771234567',
                'mobile_method' => 'ecocash',
                'payer_email' => 'payer@example.com',
            ])
            ->assertCreated();

        $this->assertNull($response->json('data.checkout_url'));
        $this->assertSame(
            'Please approve the Ecocash prompt on your phone.',
            $response->json('data.instructions')
        );

        Http::assertSent(function ($request) use ($remoteUrl) {
            return $request->url() === $remoteUrl
                && $request['method'] === 'ecocash'
                && $request['phone'] === '0771234567';
        });
    }

    public function test_paynow_webhook_rejects_invalid_hash_in_live_mode(): void
    {
        $integrationKey = 'live-integration-key';
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'amount' => 20,
            'balance' => 20,
            'status' => 'pending',
        ]);

        $config = PaymentGatewayConfig::create([
            'school_id' => $auth['school']->id,
            'provider' => 'paynow',
            'credentials' => [
                'mode' => 'live',
                'integration_id' => '12345',
                'integration_key' => $integrationKey,
            ],
            'is_active' => true,
        ]);

        Http::fake([
            config('services.paynow.initiate_url') => function () use ($integrationKey) {
                $fields = [
                    'status' => 'Ok',
                    'browserurl' => 'https://www.paynow.co.zw/Payment/ConfirmPayment/999',
                    'pollurl' => 'https://www.paynow.co.zw/Interface/CheckPayment/?guid=abc-123',
                ];
                $fields['hash'] = $this->paynowHash($fields, $integrationKey);

                return Http::response(http_build_query($fields), 200);
            },
        ]);

        $reference = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/platform/payments/initiate', [
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => 20,
                'payment_method' => 'card',
            ])
            ->assertCreated()
            ->json('data.transaction.internal_reference');

        $this->assertNotEmpty($reference);
        $this->assertSame($config->id, PaymentGatewayConfig::first()->id);

        $this->postJson('/api/v1/webhooks/payments/paynow', [
            'reference' => $reference,
            'status' => 'Paid',
            'hash' => 'BADHASH',
        ])->assertForbidden();
    }

    public function test_paynow_hash_verification_matches_client(): void
    {
        $client = new PaynowGatewayClient;
        $payload = [
            'reference' => 'PG-ABC123',
            'amount' => '10.00',
            'status' => 'Paid',
            'paynowreference' => 'PN-999',
        ];
        $payload['hash'] = $this->paynowHash($payload, 'secret-key');

        $this->assertTrue($client->verifyHash($payload, 'secret-key'));
        $this->assertFalse($client->verifyHash($payload, 'wrong-key'));
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function paynowHash(array $fields, string $integrationKey): string
    {
        $string = '';
        foreach ($fields as $key => $value) {
            if (strtoupper($key) === 'HASH') {
                continue;
            }
            $string .= $value;
        }

        return strtoupper(hash('sha512', $string.$integrationKey));
    }
}
