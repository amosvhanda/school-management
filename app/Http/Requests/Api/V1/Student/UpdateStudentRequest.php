<?php

namespace App\Http\Requests\Api\V1\Student;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\ClassModel;
use App\Rules\ZimbabweMobileNumber;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student && (bool) $this->user()?->can('update', $student);
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
        $schoolId = $this->user()?->school_id;

        return [
            'firstName' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'class' => ['sometimes', 'string'],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'grade_level_id' => [
                'nullable',
                'integer',
                Rule::exists('grade_levels', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
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
            'guardian_id' => [
                'nullable',
                'integer',
                Rule::exists('guardians', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
