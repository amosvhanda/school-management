<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesPayrollAccess;

class ProcessPayrollRequest extends ApiFormRequest
{
    use AuthorizesPayrollAccess;

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:bank_transfer,cash,ecocash,onemoney,zipit,swipe'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'amount_paid' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
