<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Invoice;
use Illuminate\Validation\Validator;

class UpdateInvoiceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && (bool) $this->user()?->can('update', $invoice);
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'string', 'max:500'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'due_date' => ['sometimes', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invoice = $this->route('invoice');
            if (! $invoice instanceof Invoice) {
                return;
            }

            if ($this->has('amount') && (float) $this->input('amount') < (float) $invoice->amount_paid) {
                $validator->errors()->add('amount', 'Invoice amount cannot be less than amount already paid.');
            }
        });
    }
}
