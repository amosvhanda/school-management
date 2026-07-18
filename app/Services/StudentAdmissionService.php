<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentAdmissionService
{
    protected GuardianService $guardianService;

    public function __construct(GuardianService $guardianService)
    {
        $this->guardianService = $guardianService;
    }

    /**
     * Admit a new student with enrollment
     */
    public function admitStudent(array $studentData, array $guardianData, int $schoolId): Student
    {
        return DB::transaction(function () use ($studentData, $guardianData, $schoolId) {
            // Validate school exists
            $school = School::findOrFail($schoolId);

            // Validate grade level belongs to school
            if (! empty($studentData['grade_level_id'])) {
                $gradeLevel = GradeLevel::where('school_id', $schoolId)
                    ->where('id', $studentData['grade_level_id'])
                    ->firstOrFail();
            }

            // Validate class belongs to school and grade level
            if (! empty($studentData['class_id'])) {
                $class = ClassModel::where('school_id', $schoolId)
                    ->where('id', $studentData['class_id'])
                    ->when(! empty($studentData['grade_level_id']), function ($query) use ($studentData) {
                        $query->where('grade_level_id', $studentData['grade_level_id']);
                    })
                    ->firstOrFail();
            }

            // Create student
            $student = Student::create([
                'student_number' => $studentData['student_number'] ?? $this->generateStudentNumber($schoolId),
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'full_name' => "{$studentData['first_name']} {$studentData['last_name']}",
                'date_of_birth' => $studentData['date_of_birth'] ?? null,
                'gender' => $studentData['gender'] ?? null,
                'national_id' => $studentData['national_id'] ?? null,
                'phone' => $studentData['phone'] ?? null,
                'email' => $studentData['email'] ?? null,
                'address' => $studentData['address'] ?? null,
                'suburb' => $studentData['suburb'] ?? null,
                'status' => 'active',
                'school_id' => $schoolId,
                'grade_level_id' => $studentData['grade_level_id'] ?? null,
                'class_id' => $studentData['class_id'] ?? null,
                'stream_id' => $studentData['stream_id'] ?? null,
                'house_id' => $studentData['house_id'] ?? null,
                'balance' => 0,
                'currency' => $school->currency_default ?? $school->currency ?? 'USD',
            ]);

            // Create user account for student if email provided
            if (! empty($studentData['email'])) {
                $user = User::create([
                    'name' => $student->full_name,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $studentData['email'],
                    'phone' => $student->phone,
                    'password' => Hash::make($studentData['password'] ?? 'password123'),
                    'role' => 'student',
                    'school_id' => $schoolId,
                ]);

                $student->update(['user_id' => $user->id]);
            }

            // Auto-create guardian
            if (! empty($guardianData)) {
                $this->guardianService->createOrFindGuardian($guardianData, $schoolId, $student->id);
            }

            // Enrollment is created by StudentPlacementService after admit when class is known.
            return $student->fresh(['gradeLevel', 'classModel', 'guardians', 'stream', 'house']);
        });
    }

    /**
     * Generate unique student number — single source for admission and direct create.
     */
    public function generateStudentNumber(int $schoolId): string
    {
        $school = School::findOrFail($schoolId);
        $code = strtoupper(substr((string) ($school->code ?: 'SCH'), 0, 3));
        $year = date('Y');

        $lastStudent = Student::where('school_id', $schoolId)
            ->where('student_number', 'like', "{$code}{$year}%")
            ->orderBy('student_number', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr((string) $lastStudent->student_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s%s%04d', $code, $year, $newNumber);
    }
}
