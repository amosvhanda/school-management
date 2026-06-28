<?php

namespace App\Http\Requests\Api\V1\Guardian;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class LinkGuardianStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->school_id !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId !== null) {
                        $query->where('school_id', $schoolId);
                    }
                }),
            ],
            'relationship' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['nullable', 'boolean'],
            'can_pickup' => ['nullable', 'boolean'],
            'emergency_contact' => ['nullable', 'boolean'],
        ];
    }
}
