<?php

namespace App\Http\Requests\Api\V1\Student;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Rules\ZimbabweMobileNumber;

class UpdateStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student && (bool) $this->user()?->can('update', $student);
    }

    public function rules(): array
    {
        return [
            'firstName' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'class' => ['sometimes', 'string'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'dateOfBirth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'phone' => ZimbabweMobileNumber::optional(),
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'suburb' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string'],
            'guardian.firstName' => ['nullable', 'string', 'max:255'],
            'guardian.surname' => ['nullable', 'string', 'max:255'],
            'guardian.phone' => ZimbabweMobileNumber::optional(),
            'guardian.email' => ['nullable', 'email'],
            'guardian.relationship' => ['nullable', 'string', 'max:100'],
            'guardian_id' => ['nullable', 'integer', 'exists:guardians,id'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
