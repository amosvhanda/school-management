<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'invoice_id' => $this->invoice_id,
            'parent_id' => $this->parent_id,
            'school_id' => $this->school_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'method' => $this->method,
            'reference' => $this->reference,
            'status' => $this->status,
            'date' => $this->date?->toDateString(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'full_name' => $this->student->full_name,
                'student_number' => $this->student->student_number,
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'balance' => $this->invoice->balance,
                'due_date' => $this->invoice->due_date?->toDateString(),
            ]),
            'school' => $this->whenLoaded('school', fn () => [
                'id' => $this->school->id,
                'name' => $this->school->name,
                'code' => $this->school->code,
            ]),
            'created_by_user' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
