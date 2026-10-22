<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;

class StoreRevenueRuleRequest extends ApiFormRequest
{
    use AuthorizesEnterpriseFinance;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'recognition_method' => ['required', 'in:immediate,monthly,term_based'],
            'config' => ['nullable', 'array'],
        ];
    }
}
