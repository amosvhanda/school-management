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
            'employment_type' => $this->employment_type,
            'base_salary' => $this->base_salary !== null ? (float) $this->base_salary : null,
            'period_rate' => $this->period_rate !== null ? (float) $this->period_rate : null,
            'salary_currency' => $this->salary_currency,
            'allowances' => $this->allowances ?? [],
            'deductions' => $this->deductions ?? [],
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'payment_method' => $this->payment_method,
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
