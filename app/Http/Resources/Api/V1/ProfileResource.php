<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Services\TeacherResolutionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->role instanceof UserRole
            ? $this->role
            : UserRole::tryFromMixed($this->role);

        $data = [
            'id' => $this->id,
            'firstName' => $this->first_name ?? explode(' ', $this->name)[0] ?? '',
            'surname' => $this->last_name ?? (count(explode(' ', $this->name)) > 1 ? explode(' ', $this->name)[1] : ''),
            'fullName' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
            'address' => $this->address ?? '',
            'role' => $role?->apiValue() ?? $this->role,
            'school_id' => $this->school_id,
            'dateOfBirth' => $this->date_of_birth?->format('Y-m-d') ?? '',
            'gender' => $this->gender ? ucfirst($this->gender) : '',
            'avatarUrl' => $this->avatar_url ?? '',
        ];

        if ($role === UserRole::Student) {
            $student = Student::query()->where('user_id', $this->id)->first();
            if ($student) {
                $data['studentId'] = $student->student_number;
                $data['class'] = $student->class;
                $data['school'] = $student->school;
            }
        }

        if ($role === UserRole::Teacher) {
            $teacher = app(TeacherResolutionService::class)->resolveForUser($this->resource);
            if ($teacher) {
                $data['employeeId'] = $teacher->employee_id;
                $data['department'] = $teacher->department;
                $data['qualification'] = $teacher->qualification;
                $data['employmentType'] = $teacher->employment_type;
                $data['joiningDate'] = $teacher->joining_date?->format('Y-m-d');
                $data['employmentStatus'] = $teacher->status;

                $assignments = TeacherAssignment::query()
                    ->where('teacher_id', $teacher->id)
                    ->where('is_active', true)
                    ->whereNotNull('class_id')
                    ->with(['classModel:id,name', 'subject:id,name'])
                    ->get();

                $subjects = $assignments->pluck('subject.name')->filter()->unique()->values();
                if ($subjects->isEmpty() && $teacher->subject) {
                    $subjects = collect([$teacher->subject]);
                }

                $data['subjects'] = $subjects->all();
                $data['classes'] = $assignments
                    ->pluck('classModel.name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        return $data;
    }
}
