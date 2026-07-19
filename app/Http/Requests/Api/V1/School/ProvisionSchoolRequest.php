<?php

namespace App\Http\Requests\Api\V1\School;

use App\Enums\UserRole;
use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProvisionSchoolRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SuperAdmin;
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255'],
            'school_code' => ['required', 'string', 'max:50', 'unique:schools,code'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', Password::default(), 'confirmed'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'currency' => ['nullable', 'string', 'max:10'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'current_term' => ['nullable', 'string', 'max:50'],
            'grade_levels' => ['nullable', 'array'],
            'grading_scale' => ['nullable', 'array'],
            'license_key' => ['nullable', 'string', 'min:10'],
            'generate_and_activate_license' => ['sometimes', 'boolean'],
            'plan_type' => [
                Rule::requiredIf(fn () => (bool) $this->boolean('generate_and_activate_license')),
                'nullable',
                'string',
                'in:lifetime,monthly,quarterly,annual,custom',
            ],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
