<?php

namespace App\Http\Requests\Api\V1\School;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Rules\ZimbabweMobileNumber;

class UpdateSchoolRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->school_id !== null
            || $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ZimbabweMobileNumber::optional(),
            'email' => ['nullable', 'email'],
            'currency' => ['nullable', 'string', 'in:USD,ZWG'],
            'academic_year' => ['nullable', 'string'],
            'current_term' => ['nullable', 'string'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'year_founded' => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
            'suburb' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'student_capacity' => ['nullable', 'integer', 'min:1'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'motto' => ['nullable', 'string', 'max:500'],
        ];
    }
}
