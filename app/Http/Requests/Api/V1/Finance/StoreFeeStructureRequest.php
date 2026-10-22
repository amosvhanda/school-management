<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeeStructureRequest extends ApiFormRequest
{
    use AuthorizesEnterpriseFinance;

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'class' => ['nullable', 'string'],
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'fee_category_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_categories', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'in:USD,ZWG,ZWL'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('fee_category_id') && ! $this->filled('category')) {
                $validator->errors()->add('fee_category_id', 'Select a fee category.');
            }
            if (! $this->filled('class_id') && ! $this->filled('class')) {
                $validator->errors()->add('class_id', 'Select a class.');
            }
        });
    }
}
