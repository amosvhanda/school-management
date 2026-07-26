<?php

namespace App\Http\Requests\Api\V1\Teacher;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Rules\ZimbabweMobileNumber;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $teacher && (bool) $this->user()?->can('update', $teacher);
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $schoolId = $teacher?->school_id ?? $this->user()?->school_id;

        return [
            'firstName' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('teachers', 'email')
                    ->where(fn ($q) => $q->where('school_id', $schoolId))
                    ->ignore($teacher?->id),
            ],
            'phone' => ZimbabweMobileNumber::optional(),
            'address' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation_id' => [
                'nullable',
                'integer',
                Rule::exists('designations', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'qualification' => ['nullable', 'string', 'max:255'],
            'joiningDate' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'on_leave'])],
            'employment_type' => ['sometimes', 'string', Rule::in(['full_time', 'part_time'])],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'period_rate' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['sometimes', 'string', Rule::in(['USD', 'ZWG'])],
            'allowances' => ['nullable', 'array'],
            'deductions' => ['nullable', 'array'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', Rule::in(['bank_transfer', 'cash', 'ecocash', 'onemoney', 'zipit', 'swipe'])],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
