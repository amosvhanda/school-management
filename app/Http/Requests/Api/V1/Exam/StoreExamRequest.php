<?php

namespace App\Http\Requests\Api\V1\Exam;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Exam;
use Illuminate\Validation\Rule;

class StoreExamRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Exam::class);
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'term_id' => [
                'required',
                'integer',
                Rule::exists('terms', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'grade_level_id' => [
                'required',
                'integer',
                Rule::exists('grade_levels', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'total_marks' => ['required', 'numeric', 'min:0'],
            'passing_marks' => ['nullable', 'numeric', 'min:0'],
            'academic_year' => ['required', 'string', 'max:9'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
