<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        foreach (Payment::with('invoice', 'student')->get() as $payment) {
            $inv = $payment->invoice;
            Transaction::updateOrCreate(
                [
                    'payment_id' => $payment->id,
                ],
                [
                    'student_id' => $payment->student_id,
                    'invoice_id' => $inv?->id,
                    'type' => 'payment',
                    'description' => 'Payment for ' . ($inv?->invoice_number ?? 'fees'),
                    'reference' => $payment->reference,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'balance' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'status' => 'completed',
                    'payment_method' => $payment->method,
                    'invoice_number' => $inv?->invoice_number,
                    'created_by' => $admin->id,
                    'school_id' => $payment->school_id,
                ]
            );
        }
    }
}
