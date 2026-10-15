<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'class_id' => $this->class_id,
            'date' => $this->date?->toDateString() ?? $this->date,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'time_in' => $this->time_in,
            'teacher_id' => $this->teacher_id,
            'subject_id' => $this->subject_id,
            'lesson_type' => $this->lesson_type,
            'marked_by' => $this->marked_by,
            'parent_notified' => (bool) ($this->parent_notified ?? false),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'locked_at' => $this->locked_at?->toIso8601String(),
            'locked_by' => $this->locked_by,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student?->id,
                'full_name' => $this->student?->full_name,
                'student_number' => $this->student?->student_number,
            ]),
            'class_model' => $this->whenLoaded('classModel', fn () => [
                'id' => $this->classModel?->id,
                'name' => $this->classModel?->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
