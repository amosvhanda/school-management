<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Finance\Concerns\AuthorizesEnterpriseFinance;
use Illuminate\Validation\Rule;

class CreateInstalmentPlanRequest extends ApiFormRequest
{
    use AuthorizesEnterpriseFinance;

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
            'invoice_id' => [
                'nullable',
                'integer',
                Rule::exists('invoices', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'total_amount' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'schedule' => ['required', 'array', 'min:1'],
            'schedule.*.due_date' => ['required', 'date'],
            'schedule.*.amount' => ['required', 'numeric'],
        ];
    }
}
