<?php

namespace App\Http\Requests\Api\V1\Teacher;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreTeacherRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', \App\Models\Teacher::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required_without_all:firstName,surname', 'string', 'max:255'],
            'firstName' => ['required_without:name', 'string', 'max:255'],
            'surname' => ['required_without:name', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'joiningDate' => ['nullable', 'date'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
