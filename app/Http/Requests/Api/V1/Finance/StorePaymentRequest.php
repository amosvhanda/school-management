<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Payment;
use App\Models\School;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends ApiFormRequest
{
    public const PAYMENT_METHODS = [
        'cash', 'ecocash', 'onemoney', 'innbucks', 'bank_transfer', 'card', 'cheque', 'other',
    ];

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Payment::class);
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'invoice_id' => [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', Rule::in(['USD', 'ZWG'])],
            'method' => ['required', 'string', 'max:50', Rule::in(self::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'income_head_id' => [
                'nullable',
                'integer',
                Rule::exists('income_heads', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)->where('is_active', true)
                ),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schoolId = $this->user()?->school_id;
            if (! $schoolId) {
                return;
            }

            $schoolCurrency = $this->user()->school?->getDefaultCurrency()
                ?? School::find($schoolId)?->getDefaultCurrency();

            if (! $schoolCurrency) {
                return;
            }

            $currency = strtoupper((string) $this->input('currency', $schoolCurrency));
            if ($currency !== strtoupper($schoolCurrency)) {
                $validator->errors()->add(
                    'currency',
                    "Payment must be in {$schoolCurrency}"
                );
            }
        });
    }
}
