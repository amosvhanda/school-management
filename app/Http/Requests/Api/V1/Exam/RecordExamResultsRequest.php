<?php

namespace App\Http\Requests\Api\V1\Exam;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\Exam;
use Illuminate\Validation\Rule;

class RecordExamResultsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $exam = $this->routeExam();
        abort_unless($exam instanceof Exam, 404);

        return (bool) $this->user()?->can('enterResults', $exam);
    }

    public function rules(): array
    {
        $exam = $this->routeExam();
        $schoolId = $this->user()?->school_id;
        $maxMarks = $exam?->total_marks ?? 0;

        return [
            'results' => ['required', 'array'],
            'results.*.student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(function ($q) use ($schoolId, $exam) {
                    if ($schoolId !== null) {
                        $q->where('school_id', $schoolId);
                    }
                    if ($exam?->grade_level_id) {
                        $q->where('grade_level_id', $exam->grade_level_id);
                    }
                }),
            ],
            'results.*.marks_obtained' => ['required', 'numeric', 'min:0', 'max:'.$maxMarks],
            'results.*.remarks' => ['nullable', 'string'],
        ];
    }

    public function exam(): Exam
    {
        $exam = $this->routeExam();
        abort_unless($exam instanceof Exam, 404);

        return $exam;
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
