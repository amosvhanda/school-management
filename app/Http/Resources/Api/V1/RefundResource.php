<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'student_id' => $this->student_id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'reference' => $this->reference,
            'school_id' => $this->school_id,
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'amount' => $this->payment->amount,
                'status' => $this->payment->status,
                'reference' => $this->payment->reference,
                'invoice_id' => $this->payment->invoice_id,
                'method' => $this->payment->method,
                'date' => $this->payment->date?->toDateString(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
