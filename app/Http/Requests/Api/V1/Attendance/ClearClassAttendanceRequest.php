<?php

namespace App\Http\Requests\Api\V1\Attendance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Attendance\Concerns\AuthorizesAttendanceManage;

class ClearClassAttendanceRequest extends ApiFormRequest
{
    use AuthorizesAttendanceManage;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'class_id' => $this->route('classId') ?? $this->route('class_id'),
            'date' => $this->route('date'),
        ]);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'class_id' => ['required', 'integer'],
        ];
    }
}
