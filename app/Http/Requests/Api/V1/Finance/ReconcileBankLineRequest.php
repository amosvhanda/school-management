<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;
use Illuminate\Validation\Rule;

class ReconcileBankLineRequest extends ApiFormRequest
{
    use AuthorizesEnterpriseFinance;

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'payment_id' => [
                'required',
                'integer',
                Rule::exists('payments', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
        ];
    }
}
