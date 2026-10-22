<?php

namespace App\Http\Requests\Api\V1\Student;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\ClassModel;
use App\Models\Student;
use App\Rules\ZimbabweMobileNumber;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Student::class);
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
        $isDraft = $this->input('status') === 'inactive';

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'class' => [
                $isDraft ? 'nullable' : 'required_without:class_id',
                'nullable',
                'string',
            ],
            'class_id' => [
                $isDraft ? 'nullable' : 'required_without:class',
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'stream_id' => [
                'nullable',
                'integer',
                Rule::exists('streams', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'house_id' => [
                'nullable',
                'integer',
                Rule::exists('houses', 'id')->when(
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
            'student_category_id' => [
                'nullable',
                'integer',
                Rule::exists('student_categories', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'student_number' => ['nullable', 'string', 'max:100'],
            'roll_number' => ['nullable', 'string', 'max:50'],
            'admission_date' => ['nullable', 'date'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'dateOfBirth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'religion' => ['nullable', 'string', 'max:100'],
            'caste' => ['nullable', 'string', 'max:100'],
            'mother_tongue' => ['nullable', 'string', 'max:100'],
            'languages_known' => ['nullable', 'string', 'max:255'],
            'phone' => ZimbabweMobileNumber::optional(),
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'suburb' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_phone' => ZimbabweMobileNumber::optional(),
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_phone' => ZimbabweMobileNumber::optional(),
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'siblings' => ['nullable', 'array'],
            'siblings.*' => ['nullable', 'string', 'max:255'],
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
