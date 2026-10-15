<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesPayrollAccess;

class UpdatePayrollRequest extends ApiFormRequest
{
    use AuthorizesPayrollAccess;

    public function rules(): array
    {
        return [
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'array'],
            'deductions' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
