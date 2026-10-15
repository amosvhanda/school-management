<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;

class ImportBankLineRequest extends ApiFormRequest
{
    use AuthorizesEnterpriseFinance;

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'reference' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
