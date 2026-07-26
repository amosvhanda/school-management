<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'suburb' => $this->suburb,
            'class' => $this->class,
            'class_id' => $this->class_id,
            'class_model' => $this->whenLoaded('classModel', fn () => [
                'id' => $this->classModel?->id,
                'name' => $this->classModel?->name,
                'form' => $this->classModel?->form,
            ]),
            'grade_level_id' => $this->grade_level_id,
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => [
                'id' => $this->gradeLevel?->id,
                'name' => $this->gradeLevel?->name,
            ]),
            'student_category_id' => $this->student_category_id,
            'student_category' => $this->whenLoaded('studentCategory', fn () => [
                'id' => $this->studentCategory?->id,
                'name' => $this->studentCategory?->name,
            ]),
            'status' => $this->status,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'school_id' => $this->school_id,
            'guardian' => $this->guardian,
            'guardians' => $this->when(
                $this->relationLoaded('guardians'),
                fn () => $this->guardians->map(fn ($guardian) => [
                    'id' => $guardian->id,
                    'first_name' => $guardian->first_name,
                    'last_name' => $guardian->last_name,
                    'full_name' => $guardian->full_name,
                    'phone' => $guardian->phone,
                    'email' => $guardian->email,
                    'relationship' => $guardian->pivot->relationship ?? $guardian->relationship,
                    'pivot' => [
                        'relationship' => $guardian->pivot->relationship ?? null,
                        'is_primary' => (bool) ($guardian->pivot->is_primary ?? false),
                        'can_pickup' => (bool) ($guardian->pivot->can_pickup ?? true),
                        'emergency_contact' => (bool) ($guardian->pivot->emergency_contact ?? false),
                    ],
                ])->values(),
            ),
            'custom_fields' => $this->when(
                $this->relationLoaded('customFieldValues') || isset($this->custom_fields),
                fn () => $this->custom_fields ?? $this->customFields
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
