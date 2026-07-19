<?php

namespace App\Http\Requests\Api\V1\School;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Rules\ZimbabweMobileNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterSchoolRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'phone' => ZimbabweMobileNumber::optional(),
            'email' => ['nullable', 'email'],
            'currency' => ['nullable', 'string', 'max:10'],
            'grade_levels' => ['nullable', 'array'],
            'grading_scale' => ['nullable', 'array'],
            'license_key' => [
                Rule::requiredIf(fn () => (bool) config('license.enforcement')),
                'nullable',
                'string',
                'min:10',
            ],
        ];
    }
}
