<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'currency' => $this->currency_default ?? $this->currency,
            'academic_year' => $this->academic_year,
            'current_term' => $this->current_term,
            'license_status' => $this->license_status,
            'license_plan' => $this->license_plan,
            'license_expires_at' => $this->license_expires_at?->toIso8601String(),
            'grade_levels' => GradeLevelResource::collection($this->whenLoaded('gradeLevels')),
            'grading_scales' => $this->whenLoaded('gradingScales'),
            'rooms' => $this->whenLoaded('rooms'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
