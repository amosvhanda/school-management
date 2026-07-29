<?php

namespace App\Services\Platform;

use App\Exceptions\DomainException;
use App\Exceptions\PaymentGatewayDisabledException;
use App\Models\Payment;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentGatewayTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    /**
     * Record a gateway payment attempt.
     *
     * Fail-closed unless PAYMENT_GATEWAY_LIVE=true. Even then this only creates
     * an initiated row until a real provider client is wired.
     */
    public function initiate(
        int $schoolId,
        int $invoiceId,
        ?int $studentId,
        float $amount,
        string $method,
        string $provider = 'stripe',
        ?string $currency = null,
    ): PaymentGatewayTransaction {
        $this->assertGatewayLive();

        $config = PaymentGatewayConfig::where('school_id', $schoolId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->firstOrFail();

        return PaymentGatewayTransaction::create([
            'school_id' => $schoolId,
            'config_id' => $config->id,
            'invoice_id' => $invoiceId,
            'student_id' => $studentId,
            'internal_reference' => 'PG-'.strtoupper(Str::random(12)),
            'amount' => $amount,
            'currency' => $currency ?? 'USD',
            'payment_method' => $method,
            'status' => 'initiated',
        ]);
    }

    public function completeWebhook(string $internalReference, array $providerResponse, ?string $sharedSecret = null): PaymentGatewayTransaction
    {
        $this->assertGatewayLive();
        $this->assertWebhookSecret($sharedSecret);

        $txn = PaymentGatewayTransaction::where('internal_reference', $internalReference)->firstOrFail();

        if ($txn->status === 'completed') {
            return $txn->fresh();
        }

        return DB::transaction(function () use ($txn, $providerResponse) {
            $txn->update([
                'status' => 'completed',
                'provider_reference' => $providerResponse['reference'] ?? $txn->provider_reference,
                'provider_response' => $providerResponse,
                'completed_at' => now(),
            ]);

            if ($txn->invoice_id && ! Payment::query()
                ->where('reference', $txn->internal_reference)
                ->exists()) {
                Payment::create([
                    'school_id' => $txn->school_id,
                    'student_id' => $txn->student_id,
                    'invoice_id' => $txn->invoice_id,
                    'amount' => $txn->amount,
                    'currency' => $txn->currency ?? 'USD',
                    'method' => 'online_'.$txn->payment_method,
                    'reference' => $txn->internal_reference,
                    'date' => now()->toDateString(),
                    'status' => 'completed',
                ]);
            }

            return $txn->fresh();
        });
    }

    private function assertGatewayLive(): void
    {
        if (config('payments.gateway_live')) {
            return;
        }

        throw DomainException::make(
            'payment_gateway_disabled',
            'Online payment gateway is disabled. Record manual payments instead, or enable PAYMENT_GATEWAY_LIVE after provider integration.',
            ['gateway' => ['Online payments are not available.']],
            503,
        );
    }

    private function assertWebhookSecret(?string $provided): void
    {
        $expected = (string) config('payments.webhook_secret', '');
        if ($expected === '') {
            throw new PaymentGatewayDisabledException('PAYMENT_GATEWAY_WEBHOOK_SECRET is not configured.');
        }

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            throw DomainException::make(
                'payment_webhook_unauthorized',
                'Invalid payment webhook secret.',
                ['webhook' => ['Unauthorized webhook callback.']],
                401,
            );
        }
    }
}
