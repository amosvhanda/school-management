<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
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
            $teacher = Teacher::query()
                ->where('user_id', $this->id)
                ->orWhere('email', $this->email)
                ->first();
            if ($teacher) {
                $data['employeeId'] = $teacher->employee_id;
                $data['department'] = $teacher->department;
                $data['subjects'] = $teacher->subject ? [$teacher->subject] : [];
            }
        }

        return $data;
    }
}
