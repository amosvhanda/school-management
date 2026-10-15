<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesPayrollAccess;
use Illuminate\Validation\Rule;

class GeneratePayrollRequest extends ApiFormRequest
{
    use AuthorizesPayrollAccess;

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => [
                'integer',
                Rule::exists('teachers', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
        ];
    }
}
