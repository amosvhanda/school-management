<?php

namespace App\Services\Platform;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundService
{
    public function request(User $user, Payment $payment, float $amount, string $reason): Refund
    {
        if ($payment->school_id !== $user->school_id) {
            abort(403);
        }

        if ($amount > (float) $payment->amount) {
            abort(422, 'Refund amount exceeds payment.');
        }

        return Refund::create([
            'school_id' => $user->school_id,
            'payment_id' => $payment->id,
            'student_id' => $payment->student_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);
    }

    public function approve(Refund $refund, User $approver): Refund
    {
        if ($refund->status !== 'pending') {
            abort(422, 'Refund is not pending.');
        }

        return DB::transaction(function () use ($refund, $approver) {
            $refund->update([
                'status' => 'processed',
                'approved_by' => $approver->id,
                'processed_at' => now(),
                'reference' => 'RF-'.strtoupper(Str::random(8)),
            ]);

            $refund->payment->update(['status' => 'refunded']);

            return $refund->fresh();
        });
    }
}
