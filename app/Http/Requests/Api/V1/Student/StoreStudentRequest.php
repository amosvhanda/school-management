<?php

namespace App\Http\Requests\Api\V1\Student;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\ClassModel;
use App\Rules\ZimbabweMobileNumber;

class StoreStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', \App\Models\Student::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('class') || ! $this->filled('class_id')) {
            return;
        }

        $schoolId = $this->user()?->school_id;
        $query = ClassModel::query()->where('id', (int) $this->class_id);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        $className = $query->value('name');
        if ($className) {
            $this->merge(['class' => $className]);
        }
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'class' => ['required_without:class_id', 'nullable', 'string'],
            'class_id' => ['required_without:class', 'nullable', 'integer', 'exists:classes,id'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'student_category_id' => ['nullable', 'integer', 'exists:student_categories,id'],
            'dateOfBirth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'phone' => ZimbabweMobileNumber::optional(),
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'suburb' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
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
