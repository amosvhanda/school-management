<?php

namespace App\Http\Requests\Api\V1\Exam;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Exam;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $exam = $this->routeExam();
        abort_unless($exam instanceof Exam, 404);

        return (bool) $this->user()?->can('update', $exam);
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'term_id' => [
                'sometimes',
                'integer',
                Rule::exists('terms', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'grade_level_id' => [
                'sometimes',
                'integer',
                Rule::exists('grade_levels', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'subject_id' => [
                'sometimes',
                'integer',
                Rule::exists('subjects', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['sometimes', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'total_marks' => ['sometimes', 'numeric', 'min:0'],
            'passing_marks' => ['nullable', 'numeric', 'min:0'],
            'academic_year' => ['sometimes', 'string', 'max:9'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    private function routeExam(): ?Exam
    {
        $id = $this->route('id');
        $schoolId = $this->user()?->school_id;

        if (! $id || ! $schoolId) {
            return null;
        }

        return Exam::query()
            ->where('school_id', $schoolId)
            ->find((int) $id);
    }
}
