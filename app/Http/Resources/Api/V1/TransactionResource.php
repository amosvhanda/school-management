<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'payroll_id' => $this->payroll_id,
            'payment_id' => $this->payment_id,
            'invoice_id' => $this->invoice_id,
            'income_head_id' => $this->income_head_id,
            'expense_head_id' => $this->expense_head_id,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'reference' => $this->reference,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'amount' => $this->type === 'income' ? $this->credit : $this->debit,
            'balance' => $this->balance,
            'is_system_generated' => (bool) ($this->payment_id || $this->payroll_id || $this->invoice_id),
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'invoice_number' => $this->invoice_number,
            'notes' => $this->notes,
            'school_id' => $this->school_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'full_name' => $this->student->full_name,
                'student_number' => $this->student->student_number,
                'class' => $this->student->class,
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'balance' => $this->invoice->balance,
                'due_date' => $this->invoice->due_date?->toDateString(),
            ]),
            'payroll' => $this->whenLoaded('payroll', function () {
                $monthLabel = $this->payroll->month
                    ? \Illuminate\Support\Carbon::create()->month((int) $this->payroll->month)->format('M')
                    : null;

                return [
                    'id' => $this->payroll->id,
                    'teacher_id' => $this->payroll->teacher_id,
                    'month' => $this->payroll->month,
                    'year' => $this->payroll->year,
                    'period' => $monthLabel && $this->payroll->year
                        ? "{$monthLabel} {$this->payroll->year}"
                        : null,
                    'net_salary' => $this->payroll->net_salary,
                    'employee_name' => $this->payroll->relationLoaded('teacher')
                        ? $this->payroll->teacher?->name
                        : null,
                    'employee_number' => $this->payroll->relationLoaded('teacher')
                        ? $this->payroll->teacher?->employee_id
                        : null,
                ];
            }),
            'income_head' => $this->whenLoaded('incomeHead', fn () => [
                'id' => $this->incomeHead->id,
                'name' => $this->incomeHead->name,
            ]),
            'expense_head' => $this->whenLoaded('expenseHead', fn () => [
                'id' => $this->expenseHead->id,
                'name' => $this->expenseHead->name,
            ]),
            'created_by' => $this->created_by,
            'created_by_user' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'date' => $this->created_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
