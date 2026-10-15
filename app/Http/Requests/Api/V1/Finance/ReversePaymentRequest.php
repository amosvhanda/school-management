<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Payment;

class ReversePaymentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment
            && (bool) $this->user()?->can('update', $payment);
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
