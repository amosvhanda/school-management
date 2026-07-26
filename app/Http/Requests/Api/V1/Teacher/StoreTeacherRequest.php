<?php

namespace App\Http\Requests\Api\V1\Teacher;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Rules\ZimbabweMobileNumber;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', \App\Models\Teacher::class);
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'name' => ['required_without_all:firstName,surname', 'string', 'max:255'],
            'firstName' => ['required_without:name', 'string', 'max:255'],
            'surname' => ['required_without:name', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('teachers', 'email')->where(fn ($q) => $q->where('school_id', $schoolId)),
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
