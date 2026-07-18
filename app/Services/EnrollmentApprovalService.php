<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\EnrollmentApplication;
use App\Models\GradeLevel;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentApprovalService
{
    public function __construct(
        private StudentAdmissionService $admissionService,
        private StudentPlacementService $placementService,
        private FinancialLedgerService $ledgerService,
    ) {}

    /**
     * Approve application: create student, ID, guardian, placement, and fee ledger.
     *
     * @param  array{class_id?:int|null,stream_id?:int|null,house_id?:int|null}  $placement
     */
    public function approve(EnrollmentApplication $application, ?int $classId, int $reviewedBy, array $placement = []): Student
    {
        if ($application->status === 'approved' && $application->student_id) {
            return Student::findOrFail($application->student_id);
        }

        return DB::transaction(function () use ($application, $classId, $reviewedBy, $placement) {
            $schoolId = $application->school_id;
            $gradeLevel = GradeLevel::query()
                ->where('school_id', $schoolId)
                ->where('name', $application->grade_applying_for)
                ->first();

            $resolvedClassId = $classId ?? ($placement['class_id'] ?? null);
            $streamId = $placement['stream_id'] ?? null;
            $houseId = $placement['house_id'] ?? null;

            $classQuery = ClassModel::query()->where('school_id', $schoolId);
            if ($resolvedClassId) {
                $class = $classQuery->findOrFail((int) $resolvedClassId);
            } else {
                $matching = (clone $classQuery)
                    ->when($gradeLevel, fn ($q) => $q->where('grade_level_id', $gradeLevel->id))
                    ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
                    ->orderBy('name')
                    ->get();

                if ($matching->count() > 1) {
                    throw ValidationException::withMessages([
                        'class_id' => ['Multiple classes match this grade. Please select a class to place the student.'],
                    ]);
                }

                $class = $matching->first();
            }

            if (! $class) {
                throw ValidationException::withMessages([
                    'class_id' => ['No class is available for this grade. Create a class before approving.'],
                ]);
            }

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
                'grade_level_id' => $gradeLevel?->id ?? $class->grade_level_id,
                'class_id' => $class->id,
                'stream_id' => $streamId ?? $class->stream_id,
                'house_id' => $houseId,
                'academic_year' => $application->academic_year,
                'previous_school' => $application->previous_school,
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

            $student = $this->placementService->place($student, [
                'class_id' => $class->id,
                'stream_id' => $streamId ?? $class->stream_id,
                'house_id' => $houseId,
                'academic_year' => (string) $application->academic_year,
                'reason' => 'admission',
                'apply_fees' => false,
            ], $reviewedBy);

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

            return $student->fresh(['gradeLevel', 'classModel.teacher', 'stream', 'house', 'guardians']);
        });
    }
}
