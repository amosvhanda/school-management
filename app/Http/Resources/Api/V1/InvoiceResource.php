<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'student_id' => $this->student_id,
            'fee_structure_id' => $this->fee_structure_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'amount_paid' => $this->amount_paid,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'school_id' => $this->school_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'full_name' => $this->student->full_name,
                'student_number' => $this->student->student_number,
                'class' => $this->student->class,
            ]),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'date' => $payment->date?->toDateString(),
            ])),
            'fee_structure' => $this->whenLoaded('feeStructure', fn () => [
                'id' => $this->feeStructure->id,
                'category' => $this->feeStructure->category,
                'amount' => $this->feeStructure->amount,
            ]),
            'school' => $this->whenLoaded('school', fn () => [
                'id' => $this->school->id,
                'name' => $this->school->name,
                'code' => $this->school->code,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
