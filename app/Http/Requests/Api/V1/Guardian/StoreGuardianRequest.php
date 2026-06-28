<?php

namespace App\Http\Requests\Api\V1\Guardian;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreGuardianRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->school_id !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'relationship' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'can_receive_notifications' => ['nullable', 'boolean'],
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists('students', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId !== null) {
                        $query->where('school_id', $schoolId);
                    }
                }),
            ],
        ];
    }
}
