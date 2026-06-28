<?php

namespace App\Http\Requests\Api\V1\Settings;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateSchoolSettingsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->school_id !== null
            || $this->user()?->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.group' => ['required', 'string'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['nullable'],
            'settings.*.type' => ['nullable', 'string'],
            'settings.*.is_public' => ['nullable', 'boolean'],
        ];
    }
}
