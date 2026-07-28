<?php

namespace App\Services\Platform;

use App\Models\Invoice;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentGatewayTransaction;
use App\Services\FinancialLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentGatewayService
{
    public function __construct(
        private FinancialLedgerService $ledger,
    ) {}

    /**
     * @return array{transaction: PaymentGatewayTransaction, checkout_url: string|null, poll_url: string|null}
     */
    public function initiate(
        int $schoolId,
        int $invoiceId,
        ?int $studentId,
        float $amount,
        string $method,
        string $provider = 'paynow',
        ?string $returnUrl = null,
    ): array {
        $config = PaymentGatewayConfig::where('school_id', $schoolId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->firstOrFail();

        $invoice = Invoice::query()
            ->where('school_id', $schoolId)
            ->findOrFail($invoiceId);

        if ($studentId === null) {
            $studentId = $invoice->student_id ? (int) $invoice->student_id : null;
        }

        if ($amount > (float) $invoice->balance + 0.001) {
            throw new InvalidArgumentException('Payment amount exceeds the invoice balance.');
        }

        $txn = PaymentGatewayTransaction::create([
            'school_id' => $schoolId,
            'config_id' => $config->id,
            'invoice_id' => $invoiceId,
            'student_id' => $studentId,
            'internal_reference' => 'PG-'.strtoupper(Str::random(12)),
            'amount' => $amount,
            'currency' => $invoice->currency ?? 'USD',
            'payment_method' => $method,
            'status' => 'initiated',
            'provider_response' => [
                'provider' => $provider,
                'return_url' => $returnUrl,
            ],
        ]);

        $checkout = $this->buildCheckout($txn, $config, $returnUrl);

        $txn->update([
            'provider_reference' => $checkout['provider_reference'] ?? null,
            'provider_response' => array_merge($txn->provider_response ?? [], $checkout['response'] ?? []),
            'status' => $checkout['status'] ?? 'initiated',
        ]);

        return [
            'transaction' => $txn->fresh(),
            'checkout_url' => $checkout['checkout_url'] ?? null,
            'poll_url' => $checkout['poll_url'] ?? null,
        ];
    }

    public function completeWebhook(string $internalReference, array $providerResponse): PaymentGatewayTransaction
    {
        $txn = PaymentGatewayTransaction::where('internal_reference', $internalReference)->firstOrFail();

        if ($txn->status === 'completed') {
            return $txn;
        }

        return DB::transaction(function () use ($txn, $providerResponse) {
            $locked = PaymentGatewayTransaction::query()
                ->whereKey($txn->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'completed') {
                return $locked;
            }

            $locked->update([
                'status' => 'completed',
                'provider_reference' => $providerResponse['reference'] ?? $locked->provider_reference,
                'provider_response' => array_merge($locked->provider_response ?? [], $providerResponse),
                'completed_at' => now(),
            ]);

            if ($locked->invoice_id) {
                $method = 'online_'.($locked->payment_method ?: 'gateway');
                $this->ledger->recordPayment(
                    invoiceId: (int) $locked->invoice_id,
                    amount: (float) $locked->amount,
                    method: $method,
                    schoolId: (int) $locked->school_id,
                    createdBy: null,
                    reference: $locked->internal_reference,
                    notes: 'Online payment via '.($providerResponse['provider'] ?? 'gateway'),
                );
            }

            return $locked->fresh();
        });
    }

    /**
     * @return array{checkout_url?: string|null, poll_url?: string|null, provider_reference?: string|null, status?: string, response?: array<string, mixed>}
     */
    protected function buildCheckout(
        PaymentGatewayTransaction $txn,
        PaymentGatewayConfig $config,
        ?string $returnUrl,
    ): array {
        $provider = strtolower((string) $config->provider);
        $credentials = $config->credentials ?? [];
        $mode = (string) ($credentials['mode'] ?? config('services.payments.mode', 'sandbox'));

        if ($provider === 'paynow') {
            return $this->buildPaynowCheckout($txn, $credentials, $returnUrl, $mode);
        }

        // Generic / Stripe-like placeholder: local sandbox redirect that can be completed via webhook.
        $checkoutUrl = url('/api/v1/webhooks/payments/'.$provider.'/sandbox-checkout?ref='.$txn->internal_reference);
        if ($returnUrl) {
            $checkoutUrl .= '&return_url='.urlencode($returnUrl);
        }

        return [
            'checkout_url' => $checkoutUrl,
            'poll_url' => url('/api/v1/platform/payments/status/'.$txn->internal_reference),
            'provider_reference' => $txn->internal_reference,
            'status' => 'pending',
            'response' => [
                'mode' => $mode,
                'provider' => $provider,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{checkout_url?: string|null, poll_url?: string|null, provider_reference?: string|null, status?: string, response?: array<string, mixed>}
     */
    protected function buildPaynowCheckout(
        PaymentGatewayTransaction $txn,
        array $credentials,
        ?string $returnUrl,
        string $mode,
    ): array {
        $integrationId = $credentials['integration_id'] ?? $credentials['id'] ?? null;
        $integrationKey = $credentials['integration_key'] ?? $credentials['key'] ?? null;

        // Without live credentials, expose a sandbox checkout that posts back to our webhook.
        if (! $integrationId || ! $integrationKey || $mode === 'sandbox') {
            Log::info('Paynow sandbox checkout initiated', [
                'reference' => $txn->internal_reference,
                'amount' => $txn->amount,
            ]);

            $checkoutUrl = url('/api/v1/webhooks/payments/paynow/sandbox-checkout?ref='.$txn->internal_reference);
            if ($returnUrl) {
                $checkoutUrl .= '&return_url='.urlencode($returnUrl);
            }

            return [
                'checkout_url' => $checkoutUrl,
                'poll_url' => url('/api/v1/platform/payments/status/'.$txn->internal_reference),
                'provider_reference' => 'PAYNOW-SB-'.$txn->internal_reference,
                'status' => 'pending',
                'response' => [
                    'mode' => 'sandbox',
                    'provider' => 'paynow',
                    'message' => 'Sandbox checkout — complete via webhook or sandbox redirect.',
                ],
            ];
        }

        // Live Paynow HTTP initiate is left as a follow-up; credentials are present so mark pending.
        return [
            'checkout_url' => null,
            'poll_url' => url('/api/v1/platform/payments/status/'.$txn->internal_reference),
            'provider_reference' => null,
            'status' => 'pending',
            'response' => [
                'mode' => 'live',
                'provider' => 'paynow',
                'message' => 'Live Paynow credentials configured; complete outbound initiate in a follow-up release.',
            ],
        ];
    }
}
