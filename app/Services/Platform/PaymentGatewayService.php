<?php

namespace App\Services\Platform;

use App\Models\Payment;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentGatewayTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function initiate(
        int $schoolId,
        int $invoiceId,
        ?int $studentId,
        float $amount,
        string $method,
        string $provider = 'stripe',
    ): PaymentGatewayTransaction {
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
            'payment_method' => $method,
            'status' => 'initiated',
        ]);
    }

    public function completeWebhook(string $internalReference, array $providerResponse): PaymentGatewayTransaction
    {
        $txn = PaymentGatewayTransaction::where('internal_reference', $internalReference)->firstOrFail();

        return DB::transaction(function () use ($txn, $providerResponse) {
            $txn->update([
                'status' => 'completed',
                'provider_reference' => $providerResponse['reference'] ?? $txn->provider_reference,
                'provider_response' => $providerResponse,
                'completed_at' => now(),
            ]);

            if ($txn->invoice_id) {
                Payment::create([
                    'school_id' => $txn->school_id,
                    'student_id' => $txn->student_id,
                    'invoice_id' => $txn->invoice_id,
                    'amount' => $txn->amount,
                    'currency' => $txn->currency,
                    'method' => 'online_'.$txn->payment_method,
                    'reference' => $txn->internal_reference,
                    'date' => now()->toDateString(),
                    'status' => 'completed',
                ]);
            }

            return $txn->fresh();
        });
    }
}
