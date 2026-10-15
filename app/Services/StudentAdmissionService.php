<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Domain\SchoolDomainRules;
use App\Support\TemporaryPassword;
use Illuminate\Support\Facades\DB;

class StudentAdmissionService
{
    public function __construct(
        protected GuardianService $guardianService,
        protected SchoolDomainRules $domainRules,
    ) {}

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

            $studentNumber = $studentData['student_number'] ?? $this->generateStudentNumber($schoolId);
            $this->domainRules->assertAdmissionNumberAvailable($schoolId, (string) $studentNumber);

            // Create student
            $student = Student::create([
                'student_number' => $studentNumber,
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
                $providedPassword = ! empty($studentData['password']);
                $user = User::create([
                    'name' => $student->full_name,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'email' => $studentData['email'],
                    'phone' => $student->phone,
                    'password' => $providedPassword
                        ? (string) $studentData['password']
                        : TemporaryPassword::generate(),
                    'must_change_password' => ! $providedPassword,
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
     *
     * Format: {SCHOOL_CODE}-{YEAR}-{####}
     * Example: MUF001-2026-0001 (school code MUF001, year 2026, sequence 1)
     *
     * Numbers are assigned once at create/admission and are not changed on
     * class placement or promotion.
     */
    public function generateStudentNumber(int $schoolId): string
    {
        $school = School::findOrFail($schoolId);
        $rawCode = strtoupper((string) ($school->code ?: 'SCH'));
        $code = preg_replace('/[^A-Z0-9]/', '', $rawCode) ?: 'SCH';
        $year = date('Y');
        $prefix = "{$code}-{$year}-";

        $lastStudent = Student::where('school_id', $schoolId)
            ->where('student_number', 'like', $prefix.'%')
            ->orderByDesc('student_number')
            ->first();

        $next = 1;
        if ($lastStudent) {
            $tail = (string) substr((string) $lastStudent->student_number, strlen($prefix));
            // Prefer numeric sequences (0001); ignore demo suffixes like F3.
            if (ctype_digit($tail)) {
                $next = ((int) $tail) + 1;
            } else {
                $count = Student::where('school_id', $schoolId)
                    ->where('student_number', 'like', $prefix.'%')
                    ->count();
                $next = $count + 1;
            }
        }

        return sprintf('%s%04d', $prefix, $next);
    }
}
