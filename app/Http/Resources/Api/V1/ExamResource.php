<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'name' => $this->name,
            'description' => $this->description,
            'term_id' => $this->term_id,
            'grade_level_id' => $this->grade_level_id,
            'subject_id' => $this->subject_id,
            'exam_date' => $this->exam_date?->toDateString() ?? $this->exam_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'total_marks' => $this->total_marks,
            'passing_marks' => $this->passing_marks,
            'academic_year' => $this->academic_year,
            'is_published' => (bool) ($this->is_published ?? false),
            'results_approved_at' => $this->results_approved_at?->toIso8601String(),
            'exam_results_count' => $this->when(isset($this->exam_results_count), $this->exam_results_count),
            'term' => $this->whenLoaded('term', fn () => [
                'id' => $this->term?->id,
                'name' => $this->term?->name,
            ]),
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => [
                'id' => $this->gradeLevel?->id,
                'name' => $this->gradeLevel?->name,
            ]),
            'subject' => $this->whenLoaded('subject', fn () => [
                'id' => $this->subject?->id,
                'name' => $this->subject?->name,
                'code' => $this->subject?->code,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
