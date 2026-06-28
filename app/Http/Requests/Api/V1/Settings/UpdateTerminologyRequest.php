<?php

namespace App\Http\Requests\Api\V1\Settings;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateTerminologyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->school_id !== null
            || $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'locale' => ['nullable', 'string', 'max:10'],
            'mappings' => ['required', 'array'],
            'mappings.*' => ['required', 'string', 'max:255'],
        ];
    }
}
