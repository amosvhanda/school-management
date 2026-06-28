<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'subject' => $this->subject,
            'department' => $this->department,
            'qualification' => $this->qualification,
            'joining_date' => $this->joining_date?->format('Y-m-d'),
            'status' => $this->status,
            'school_id' => $this->school_id,
            'custom_fields' => $this->when(
                $this->relationLoaded('customFieldValues') || isset($this->custom_fields),
                fn () => $this->custom_fields ?? $this->customFields
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
