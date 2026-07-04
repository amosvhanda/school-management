<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $teacher = $this->teacher;
        $school = $this->school;
        $amountPaid = (float) ($this->amount_paid ?? 0);
        $balance = max((float) $this->net_salary - $amountPaid, 0);

        return [
            'id' => $this->id,
            'payslip_number' => "PS-{$this->year}-{$this->month}-{$this->id}",
            'generated_at' => now()->toDateTimeString(),
            'school' => [
                'id' => $school?->id,
                'name' => $school?->name,
                'code' => $school?->code,
                'address' => $school?->address,
                'phone' => $school?->phone,
                'email' => $school?->email,
            ],
            'employee' => [
                'id' => $teacher?->id,
                'name' => $teacher?->name,
                'employee_id' => $teacher?->employee_id,
                'department' => $teacher?->department,
                'position' => $teacher?->subject,
                'bank_name' => $teacher?->bank_name,
                'bank_account_number' => $teacher?->bank_account_number,
            ],
            'period' => [
                'month' => $this->month,
                'year' => $this->year,
                'month_name' => date('F', mktime(0, 0, 0, $this->month, 1)),
            ],
            'earnings' => [
                'base_salary' => (float) $this->base_salary,
                'allowances' => $this->allowances ?? [],
                'allowances_total' => (float) $this->allowances_total,
                'gross_salary' => (float) $this->gross_salary,
            ],
            'deductions' => [
                'breakdown' => $this->deductions ?? [],
                'total' => (float) $this->deductions_total,
            ],
            'summary' => [
                'gross_salary' => (float) $this->gross_salary,
                'total_deductions' => (float) $this->deductions_total,
                'net_salary' => (float) $this->net_salary,
                'amount_paid' => $amountPaid,
                'balance' => $balance,
                'currency' => $this->currency,
            ],
            'payment' => [
                'status' => $this->status,
                'paid_at' => $this->paid_at?->toDateString(),
                'payment_method' => $this->payment_method,
                'payment_reference' => $this->payment_reference,
            ],
        ];
    }
}
