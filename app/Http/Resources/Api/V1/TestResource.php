<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'term_id' => $this->term_id,
            'name' => $this->name,
            'description' => $this->description,
            'test_date' => $this->test_date?->toDateString(),
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'total_marks' => $this->total_marks,
            'passing_marks' => $this->passing_marks,
            'academic_year' => $this->academic_year,
            'is_published' => (bool) $this->is_published,
            'class' => $this->whenLoaded('classModel', fn () => [
                'id' => $this->classModel?->id,
                'name' => $this->classModel?->name,
                'form' => $this->classModel?->form,
            ]),
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject?->id,
                'name' => $this->subject?->name,
            ]),
            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher?->id,
                'name' => $this->teacher?->name,
            ]),
            'term' => $this->whenLoaded('term', fn () => [
                'id' => $this->term?->id,
                'name' => $this->term?->name,
                'academic_year' => $this->term?->academic_year,
            ]),
            'students' => $this->when(isset($this->students), fn () => $this->students->map(fn ($student) => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
            ])),
            'test_results' => $this->whenLoaded('testResults', fn () => $this->testResults->map(fn ($result) => [
                'id' => $result->id,
                'student_id' => $result->student_id,
                'marks_obtained' => $result->marks_obtained,
                'total_marks' => $result->total_marks,
                'remarks' => $result->remarks,
                'student' => $result->relationLoaded('student') ? [
                    'id' => $result->student?->id,
                    'full_name' => $result->student?->full_name,
                    'student_number' => $result->student?->student_number,
                ] : null,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
