<?php

namespace App\Http\Requests\Api\V1\Attendance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Http\Requests\Api\V1\Attendance\Concerns\AuthorizesAttendanceManage;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends ApiFormRequest
{
    use AuthorizesAttendanceManage;

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'class_id' => ['required'],
            'date' => ['required', 'date'],
            'overwrite' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
            'records' => ['required', 'array'],
            'records.*.student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'records.*.status' => ['required', 'string', 'in:present,absent,late,excused,sick,left_early'],
            'records.*.remarks' => ['nullable', 'string'],
            'records.*.time' => ['nullable', 'string'],
        ];
    }
}
