<?php

namespace App\Services\Students;

use App\Models\School;
use App\Models\Student;
use App\Services\SchoolSettingsService;

class StudentIdCardService
{
    public function __construct(
        private SchoolSettingsService $settings,
    ) {}

    public function buildPayload(Student $student): array
    {
        $school = School::find($student->school_id);
        $branding = [];

        if ($school) {
            $settings = $this->settings
                ->getAll($school, publicOnly: true);
            $branding = $settings['branding'] ?? [];
        }

        return [
            'document_number' => 'ID-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'issued_at' => now()->toIso8601String(),
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'student_number' => $student->student_number,
                'class' => $student->classModel?->name ?? $student->class,
                'grade_level' => $student->gradeLevel?->name,
                'house' => $student->house?->name,
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'gender' => $student->gender,
                'photo_url' => $student->photo_url ?? $student->user?->avatar_url,
            ],
            'school' => [
                'id' => $school?->id,
                'name' => $branding['school_name'] ?? $school?->name,
                'code' => $school?->code,
                'address' => $school?->address,
                'phone' => $school?->phone,
                'email' => $school?->email,
                'logo_path' => $school?->logo_path,
                'motto' => $branding['motto'] ?? $school?->motto,
                'primary_color' => $branding['primary_color'] ?? '#FF7A00',
            ],
        ];
    }
}
