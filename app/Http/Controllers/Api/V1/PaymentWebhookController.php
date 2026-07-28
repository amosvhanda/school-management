<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewayTransaction;
use App\Services\Platform\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentWebhookController extends Controller
{
    public function __construct(private PaymentGatewayService $gateway) {}

    public function handle(Request $request, string $provider)
    {
        $data = Validator::make($request->all(), [
            'reference' => 'required_without:ref|string',
            'ref' => 'required_without:reference|string',
            'status' => 'nullable|string',
            'provider_reference' => 'nullable|string',
            'hash' => 'nullable|string',
        ])->validate();

        $internalReference = $data['reference'] ?? $data['ref'];
        $status = strtolower((string) ($data['status'] ?? 'paid'));

        if (! in_array($status, ['paid', 'completed', 'success', 'ok'], true)) {
            return response()->json([
                'message' => 'Webhook ignored — payment not successful.',
                'status' => $status,
            ]);
        }

        $txn = PaymentGatewayTransaction::where('internal_reference', $internalReference)->firstOrFail();
        $config = $txn->config;

        if ($config && ! $this->verifySignature($request, $provider, $config->credentials ?? [])) {
            return response()->json(['message' => 'Invalid webhook signature'], 403);
        }

        $completed = $this->gateway->completeWebhook($internalReference, [
            'provider' => $provider,
            'reference' => $data['provider_reference'] ?? $request->input('paynowreference') ?? $internalReference,
            'status' => $status,
            'raw' => $request->except(['hash', 'integration_key', 'key']),
        ]);

        return response()->json([
            'message' => 'Payment completed',
            'data' => [
                'internal_reference' => $completed->internal_reference,
                'status' => $completed->status,
                'invoice_id' => $completed->invoice_id,
            ],
        ]);
    }

    /**
     * Sandbox browser checkout — completes the payment then redirects (or returns JSON).
     */
    public function sandboxCheckout(Request $request, string $provider)
    {
        $ref = (string) $request->query('ref', '');
        if ($ref === '') {
            abort(404);
        }

        $completed = $this->gateway->completeWebhook($ref, [
            'provider' => $provider,
            'reference' => 'SANDBOX-'.$ref,
            'status' => 'paid',
            'mode' => 'sandbox',
        ]);

        $returnUrl = $request->query('return_url');
        if (is_string($returnUrl) && $returnUrl !== '') {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';

            return redirect()->away($returnUrl.$separator.'payment_ref='.$completed->internal_reference.'&payment_status=completed');
        }

        return response()->json([
            'message' => 'Sandbox payment completed',
            'data' => [
                'internal_reference' => $completed->internal_reference,
                'status' => $completed->status,
            ],
        ]);
    }

    public function status(string $reference)
    {
        $txn = PaymentGatewayTransaction::where('internal_reference', $reference)->firstOrFail();

        return response()->json([
            'data' => [
                'internal_reference' => $txn->internal_reference,
                'status' => $txn->status,
                'amount' => $txn->amount,
                'currency' => $txn->currency,
                'invoice_id' => $txn->invoice_id,
                'completed_at' => $txn->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function verifySignature(Request $request, string $provider, array $credentials): bool
    {
        $expected = $credentials['webhook_secret'] ?? $credentials['integration_key'] ?? null;
        if (! $expected) {
            // Sandbox / unconfigured secret — allow (still requires knowing the internal reference).
            return true;
        }

        $provided = $request->header('X-Webhook-Secret')
            ?? $request->input('hash')
            ?? $request->input('signature');

        if (! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals((string) $expected, $provided);
    }
}
