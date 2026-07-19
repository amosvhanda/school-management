<?php

namespace App\Http\Requests\Api\V1\Settings;

use App\Models\CustomField;
use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomFieldRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('customField'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'field_type' => ['nullable', 'string', Rule::in(CustomField::FIELD_TYPES)],
            'options' => ['nullable', 'array'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
