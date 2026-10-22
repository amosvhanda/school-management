<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Invoice;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Invoice::class);
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'description' => ['required_without:fee_group_id', 'nullable', 'string', 'max:500'],
            'amount' => ['required_without:fee_group_id', 'nullable', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'fee_structure_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_structures', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'fee_group_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_groups', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)->where('is_active', true)
                ),
            ],
            'apply_discounts' => ['nullable', 'boolean'],
            'combine_group' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('fee_group_id') || $this->filled('amount')) {
                return;
            }

            $validator->errors()->add('amount', 'Enter an amount or select a fee group.');
        });
    }
}
