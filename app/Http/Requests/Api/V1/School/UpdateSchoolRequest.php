<?php

namespace App\Http\Requests\Api\V1\School;

use App\Http\Requests\Api\V1\ApiFormRequest;

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
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'currency' => ['nullable', 'string', 'max:10'],
            'academic_year' => ['nullable', 'string'],
            'current_term' => ['nullable', 'string'],
        ];
    }
}
