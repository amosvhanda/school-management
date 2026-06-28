<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class EnrollmentApprovalService
{
    public function __construct(
        private StudentAdmissionService $admissionService,
        private FinancialLedgerService $ledgerService,
    ) {}

    /**
     * Approve application: create student, ID, guardian, enrollment, and fee ledger.
     */
    public function approve(EnrollmentApplication $application, ?int $classId, int $reviewedBy): Student
    {
        if ($application->status === 'approved' && $application->student_id) {
            return Student::findOrFail($application->student_id);
        }

        return DB::transaction(function () use ($application, $classId, $reviewedBy) {
            $schoolId = $application->school_id;
            $gradeLevel = GradeLevel::query()
                ->where('school_id', $schoolId)
                ->where('name', $application->grade_applying_for)
                ->first();

            $class = $classId
                ? ClassModel::where('school_id', $schoolId)->findOrFail($classId)
                : ClassModel::query()
                    ->where('school_id', $schoolId)
                    ->when($gradeLevel, fn ($q) => $q->where('grade_level_id', $gradeLevel->id))
                    ->orderBy('name')
                    ->first();

            $studentData = [
                'first_name' => $application->first_name,
                'last_name' => $application->surname,
                'date_of_birth' => $application->date_of_birth?->toDateString(),
                'gender' => $application->gender,
                'national_id' => $application->national_id,
                'phone' => $application->phone,
                'email' => $application->email,
                'address' => $application->address,
                'suburb' => $application->suburb,
                'grade_level_id' => $gradeLevel?->id ?? $class?->grade_level_id,
                'class_id' => $class?->id,
                'academic_year' => $application->academic_year,
            ];

            $guardianData = [
                'first_name' => $application->guardian_first_name,
                'last_name' => $application->guardian_surname,
                'phone' => $application->guardian_phone,
                'email' => $application->guardian_email,
                'relationship' => $application->guardian_relationship,
                'address' => $application->guardian_address,
            ];

            $student = $this->admissionService->admitStudent($studentData, $guardianData, $schoolId);

            if ($class) {
                $student->update([
                    'class_id' => $class->id,
                    'class' => $class->name,
                    'grade_level_id' => $class->grade_level_id ?? $gradeLevel?->id,
                ]);
            }

            $this->ledgerService->applyClassFeeStructures(
                student: $student->fresh(),
                classId: $student->class_id,
                contextLabel: 'Admission fees',
                createdBy: $reviewedBy,
            );

            $this->ledgerService->applyClassFeeStructures(
                student: $student->fresh(),
                classId: $student->class_id,
                contextLabel: 'New student uniform bundle',
                createdBy: $reviewedBy,
                categoryFilter: ['Uniform', 'uniform'],
            );

            $application->update([
                'status' => 'approved',
                'student_id' => $student->id,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ]);

            return $student->fresh(['gradeLevel', 'classModel', 'guardians']);
        });
    }
}
