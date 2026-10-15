<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;

class StoreExchangeRateRequest extends ApiFormRequest
{
    use AuthorizesEnterpriseFinance;

    public function rules(): array
    {
        return [
            'from_currency' => ['required', 'string', 'size:3'],
            'to_currency' => ['required', 'string', 'size:3'],
            'rate' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
        ];
    }
}
