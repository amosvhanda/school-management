<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Create payments linked to invoices and parents.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        $invoices = Invoice::whereIn('status', ['pending', 'partial'])
            ->whereNotNull('school_id')
            ->get();
        
        $methods = ZimbabweData::PAYMENT_METHODS;
        $count = 0;
        $limit = 80;

        foreach ($invoices as $inv) {
            if ($count >= $limit) {
                break;
            }
            
            $amount = (float) $inv->balance;
            $partial = rand(0, 1);
            $payAmount = $partial ? $amount * (rand(30, 70) / 100) : $amount;
            $payAmount = round($payAmount, 2);
            
            if ($payAmount <= 0) {
                continue;
            }
            
            $ref = 'REF-' . $inv->school_id . '-' . $inv->id . '-' . substr(md5($inv->invoice_number), 0, 8);
            $date = now()->subDays(rand(0, 14))->format('Y-m-d');

            // Use parent from invoice, or get from student's parent relationship
            $parentId = $inv->parent_id;
            if (!$parentId) {
                // Try to get parent from parent_student relationship
                $parentRecord = \DB::table('parent_student')
                    ->where('student_id', $inv->student_id)
                    ->where('is_primary', true)
                    ->first();
                $parentId = $parentRecord?->parent_id;
            }

            Payment::updateOrCreate(
                [
                    'invoice_id' => $inv->id,
                    'reference' => $ref,
                ],
                [
                    'student_id' => $inv->student_id,
                    'parent_id' => $parentId, // Link to parent
                    'amount' => $payAmount,
                    'currency' => $inv->currency,
                    'method' => $methods[array_rand($methods)],
                    'status' => 'completed',
                    'date' => $date,
                    'created_by' => $admin->id,
                    'school_id' => $inv->school_id,
                ]
            );

            // Update invoice balance
            $inv->refresh();
            $inv->amount_paid = $inv->payments()->sum('amount');
            $inv->balance = $inv->amount - $inv->amount_paid;
            $inv->status = $inv->balance <= 0 ? 'paid' : 'partial';
            $inv->save();
            
            $count++;
        }
    }
}
