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
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'reference' => $this->reference,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'balance' => $this->balance,
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
            'payroll' => $this->whenLoaded('payroll', fn () => [
                'id' => $this->payroll->id,
                'teacher_id' => $this->payroll->teacher_id,
                'month' => $this->payroll->month,
                'year' => $this->payroll->year,
                'net_salary' => $this->payroll->net_salary,
            ]),
            'created_by' => $this->created_by,
            'created_by_user' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
