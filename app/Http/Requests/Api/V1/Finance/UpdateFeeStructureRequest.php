<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;
use Illuminate\Validation\Rule;

class UpdateFeeStructureRequest extends ApiFormRequest
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
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'in:USD,ZWG,ZWL'],
        ];
    }
}
