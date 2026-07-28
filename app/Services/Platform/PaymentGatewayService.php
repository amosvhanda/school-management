<?php

namespace App\Services\Platform;

use App\Models\Invoice;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentGatewayTransaction;
use App\Services\Enterprise\EnterpriseWebhookDispatcher;
use App\Services\FinancialLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PaymentGatewayService
{
    public function __construct(
        private FinancialLedgerService $ledger,
        private PaynowGatewayClient $paynow,
    ) {}

    /**
     * @param  array{
     *     phone?: string|null,
     *     mobile_method?: string|null,
     *     payer_email?: string|null,
     *     additional_info?: string|null
     * }  $options
     * @return array{
     *     transaction: PaymentGatewayTransaction,
     *     checkout_url: string|null,
     *     poll_url: string|null,
     *     instructions: string|null
     * }
     */
    public function initiate(
        int $schoolId,
        int $invoiceId,
        ?int $studentId,
        float $amount,
        string $method,
        string $provider = 'paynow',
        ?string $returnUrl = null,
        array $options = [],
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

        $checkout = $this->buildCheckout($txn, $config, $returnUrl, $options);

        $txn->update([
            'provider_reference' => $checkout['provider_reference'] ?? null,
            'provider_response' => array_merge($txn->provider_response ?? [], $checkout['response'] ?? []),
            'status' => $checkout['status'] ?? 'initiated',
        ]);

        return [
            'transaction' => $txn->fresh(),
            'checkout_url' => $checkout['checkout_url'] ?? null,
            'poll_url' => $checkout['poll_url'] ?? null,
            'instructions' => $checkout['instructions'] ?? null,
        ];
    }

    public function completeWebhook(string $internalReference, array $providerResponse): PaymentGatewayTransaction
    {
        $txn = PaymentGatewayTransaction::where('internal_reference', $internalReference)->firstOrFail();

        if ($txn->status === 'completed') {
            return $txn;
        }

        $newlyCompleted = false;

        $completed = DB::transaction(function () use ($txn, $providerResponse, &$newlyCompleted) {
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

            $newlyCompleted = true;

            return $locked->fresh();
        });

        if ($newlyCompleted) {
            app(EnterpriseWebhookDispatcher::class)->dispatch(
                (int) $completed->school_id,
                'payment.completed',
                [
                    'internal_reference' => $completed->internal_reference,
                    'invoice_id' => $completed->invoice_id,
                    'student_id' => $completed->student_id,
                    'amount' => (float) $completed->amount,
                    'currency' => $completed->currency,
                    'provider' => $providerResponse['provider'] ?? null,
                ],
            );
        }

        return $completed;
    }

    public function refreshStatus(PaymentGatewayTransaction $txn): PaymentGatewayTransaction
    {
        if ($txn->status === 'completed') {
            return $txn;
        }

        $config = $txn->config;
        if (! $config || strtolower((string) $config->provider) !== 'paynow') {
            return $txn;
        }

        $credentials = $config->credentials ?? [];
        $integrationKey = $credentials['integration_key'] ?? $credentials['key'] ?? null;
        $pollUrl = $txn->provider_response['poll_url'] ?? $txn->provider_response['pollurl'] ?? null;

        if (! $integrationKey || ! is_string($pollUrl) || $pollUrl === '') {
            return $txn;
        }

        try {
            $poll = $this->paynow->poll($pollUrl, (string) $integrationKey);
        } catch (RuntimeException $e) {
            Log::warning('Paynow poll failed', [
                'reference' => $txn->internal_reference,
                'error' => $e->getMessage(),
            ]);

            return $txn;
        }

        if (! $poll['paid']) {
            return $txn;
        }

        return $this->completeWebhook($txn->internal_reference, [
            'provider' => 'paynow',
            'reference' => $poll['paynowreference'] ?? $txn->provider_reference,
            'status' => $poll['status'],
            'poll' => true,
        ]);
    }

    /**
     * @param  array{
     *     phone?: string|null,
     *     mobile_method?: string|null,
     *     payer_email?: string|null,
     *     additional_info?: string|null
     * }  $options
     * @return array{
     *     checkout_url?: string|null,
     *     poll_url?: string|null,
     *     instructions?: string|null,
     *     provider_reference?: string|null,
     *     status?: string,
     *     response?: array<string, mixed>
     * }
     */
    protected function buildCheckout(
        PaymentGatewayTransaction $txn,
        PaymentGatewayConfig $config,
        ?string $returnUrl,
        array $options = [],
    ): array {
        $provider = strtolower((string) $config->provider);
        $credentials = $config->credentials ?? [];
        $mode = (string) ($credentials['mode'] ?? config('services.payments.mode', 'sandbox'));

        if ($provider === 'paynow') {
            return $this->buildPaynowCheckout($txn, $credentials, $returnUrl, $mode, $options);
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
     * @param  array{
     *     phone?: string|null,
     *     mobile_method?: string|null,
     *     payer_email?: string|null,
     *     additional_info?: string|null
     * }  $options
     * @return array{
     *     checkout_url?: string|null,
     *     poll_url?: string|null,
     *     instructions?: string|null,
     *     provider_reference?: string|null,
     *     status?: string,
     *     response?: array<string, mixed>
     * }
     */
    protected function buildPaynowCheckout(
        PaymentGatewayTransaction $txn,
        array $credentials,
        ?string $returnUrl,
        string $mode,
        array $options = [],
    ): array {
        $integrationId = $credentials['integration_id'] ?? $credentials['id'] ?? null;
        $integrationKey = $credentials['integration_key'] ?? $credentials['key'] ?? null;

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

        $resultUrl = url('/api/v1/webhooks/payments/paynow');
        $browserReturnUrl = $returnUrl ?: url('/');
        $additionalInfo = $options['additional_info'] ?? ('Invoice #'.$txn->invoice_id);
        $authEmail = $options['payer_email'] ?? null;
        $phone = $options['phone'] ?? null;
        $mobileMethod = strtolower((string) ($options['mobile_method'] ?? 'ecocash'));

        try {
            if ($txn->payment_method === 'mobile_money' && is_string($phone) && $phone !== '') {
                $response = $this->paynow->initiateMobile(
                    integrationId: (string) $integrationId,
                    integrationKey: (string) $integrationKey,
                    reference: $txn->internal_reference,
                    amount: (float) $txn->amount,
                    returnUrl: $browserReturnUrl,
                    resultUrl: $resultUrl,
                    phone: $phone,
                    method: $mobileMethod,
                    additionalInfo: is_string($additionalInfo) ? $additionalInfo : null,
                    authEmail: is_string($authEmail) ? $authEmail : null,
                );
            } else {
                $response = $this->paynow->initiateWeb(
                    integrationId: (string) $integrationId,
                    integrationKey: (string) $integrationKey,
                    reference: $txn->internal_reference,
                    amount: (float) $txn->amount,
                    returnUrl: $browserReturnUrl,
                    resultUrl: $resultUrl,
                    additionalInfo: is_string($additionalInfo) ? $additionalInfo : null,
                    authEmail: is_string($authEmail) ? $authEmail : null,
                );
            }
        } catch (RuntimeException $e) {
            Log::error('Paynow live initiate failed', [
                'reference' => $txn->internal_reference,
                'error' => $e->getMessage(),
            ]);

            throw new InvalidArgumentException('Paynow could not start the payment: '.$e->getMessage());
        }

        return [
            'checkout_url' => $response['browserurl'] ?? null,
            'poll_url' => $response['pollurl'] ?? url('/api/v1/platform/payments/status/'.$txn->internal_reference),
            'instructions' => $response['instructions'] ?? null,
            'provider_reference' => $response['paynowreference'] ?? null,
            'status' => 'pending',
            'response' => [
                'mode' => 'live',
                'provider' => 'paynow',
                'poll_url' => $response['pollurl'] ?? null,
                'instructions' => $response['instructions'] ?? null,
                'paynow_status' => $response['status'] ?? null,
            ],
        ];
    }
}
