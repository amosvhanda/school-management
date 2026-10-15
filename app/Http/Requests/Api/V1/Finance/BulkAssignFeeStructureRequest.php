<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;
use Illuminate\Validation\Rule;

class BulkAssignFeeStructureRequest extends ApiFormRequest
{
    use AuthorizesEnterpriseFinance;

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'fee_structure_id' => [
                'required',
                'integer',
                Rule::exists('fee_structures', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'class_ids' => ['required', 'array', 'min:1', 'max:100'],
            'class_ids.*' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
        ];
    }
}
