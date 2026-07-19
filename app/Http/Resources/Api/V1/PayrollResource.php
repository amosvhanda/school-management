<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $monthLabel = $this->month
            ? \Illuminate\Support\Carbon::create()->month((int) $this->month)->format('M')
            : null;

        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'teacher_id' => $this->teacher_id,
            'employee_name' => $this->whenLoaded('teacher', fn () => $this->teacher?->name),
            'employee_number' => $this->whenLoaded('teacher', fn () => $this->teacher?->employee_id),
            'period' => $monthLabel && $this->year ? "{$monthLabel} {$this->year}" : null,
            'month' => $this->month,
            'year' => $this->year,
            'base_salary' => $this->base_salary,
            'allowances' => $this->allowances ?? [],
            'allowances_total' => $this->allowances_total,
            'gross_salary' => $this->gross_salary,
            'deductions' => $this->deductions ?? [],
            'deductions_total' => $this->deductions_total,
            'net_salary' => $this->net_salary,
            'amount_paid' => $this->amount_paid,
            'remaining_balance' => max(
                0,
                round((float) $this->net_salary - (float) $this->amount_paid, 2)
            ),
            'currency' => $this->currency,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toDateString(),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'processed_by' => $this->processed_by,
            'notes' => $this->notes,
            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
                'employee_id' => $this->teacher->employee_id,
                'department' => $this->teacher->department,
                'subject' => $this->teacher->subject,
                'bank_name' => $this->teacher->bank_name,
                'bank_account_number' => $this->teacher->bank_account_number,
            ]),
            'payments' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($transaction) => [
                'id' => $transaction->id,
                'amount' => $transaction->debit,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'payment_method' => $transaction->payment_method,
                'reference' => $transaction->reference,
                'date' => $transaction->created_at?->toDateString(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
