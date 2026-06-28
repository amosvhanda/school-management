<?php

namespace App\Http\Requests\Api\V1\Teacher;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateTeacherRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $teacher && (bool) $this->user()?->can('update', $teacher);
    }

    public function rules(): array
    {
        return [
            'firstName' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'joiningDate' => ['nullable', 'date'],
            'status' => ['sometimes', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
