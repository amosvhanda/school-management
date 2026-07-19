<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\GradeLevelSubject;
use App\Models\House;
use App\Models\Stream;
use App\Models\Student;
use App\Services\Domain\SchoolDomainRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentPlacementService
{
    public function __construct(
        private FinancialLedgerService $ledgerService,
        private SchoolDomainRules $domainRules,
    ) {}

    /**
     * Place or re-place a student into class/stream/house for an academic year.
     *
     * @param  array{class_id:int,stream_id?:int|null,house_id?:int|null,academic_year:string,reason?:string,apply_fees?:bool,effective_date?:string}  $payload
     */
    public function place(Student $student, array $payload, ?int $actorId = null): Student
    {
        $this->domainRules->assertStudentActive($student);

        $class = ClassModel::query()
            ->where('school_id', $student->school_id)
            ->with('teacher:id,first_name,last_name,full_name')
            ->findOrFail((int) $payload['class_id']);

        $streamId = isset($payload['stream_id']) ? (int) $payload['stream_id'] : ($class->stream_id ? (int) $class->stream_id : null);
        $houseId = isset($payload['house_id']) ? (int) $payload['house_id'] : null;
        $academicYear = (string) $payload['academic_year'];
        $reason = (string) ($payload['reason'] ?? 'placement');
        $applyFees = (bool) ($payload['apply_fees'] ?? false);
        $effectiveDate = $payload['effective_date'] ?? now()->toDateString();

        if ($streamId) {
            Stream::query()
                ->where('school_id', $student->school_id)
                ->where('id', $streamId)
                ->where('is_active', true)
                ->firstOrFail();
        }

        if ($houseId) {
            House::query()
                ->where('school_id', $student->school_id)
                ->where('id', $houseId)
                ->where('is_active', true)
                ->firstOrFail();
        }

        if ($class->stream_id && $streamId && (int) $class->stream_id !== $streamId) {
            throw ValidationException::withMessages([
                'stream_id' => ['Selected class does not belong to the chosen stream.'],
            ]);
        }

        $isSamePlacement = (int) $student->class_id === (int) $class->id
            && (int) ($student->stream_id ?? 0) === (int) ($streamId ?? 0)
            && (int) ($student->house_id ?? 0) === (int) ($houseId ?? 0);

        if ($isSamePlacement && $reason !== 'admission') {
            return $student->fresh(['classModel.teacher', 'stream', 'house', 'gradeLevel']);
        }

        // Explicit enroll without transfer flag cannot join a second active class.
        $allowTransfer = (bool) ($payload['force_transfer'] ?? true);
        if (! $allowTransfer) {
            $this->domainRules->assertNoOtherActiveClass($student, (int) $class->id);
        }

        $this->domainRules->assertSingleActiveEnrollment($student);
        $this->assertCapacity($class, $student);

        return DB::transaction(function () use ($student, $class, $streamId, $houseId, $academicYear, $reason, $applyFees, $effectiveDate, $actorId) {
            $previousClassId = (int) ($student->class_id ?? 0);

            Enrollment::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['active', 'repeating'])
                ->update([
                    'status' => 'completed',
                    'left_at' => $effectiveDate,
                ]);

            Enrollment::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'class_id' => $class->id,
                'stream_id' => $streamId,
                'house_id' => $houseId,
                'academic_year' => $academicYear,
                'enrolled_at' => $effectiveDate,
                'status' => 'active',
                'reason' => $reason,
                'changed_by' => $actorId,
            ]);

            $student->update([
                'class_id' => $class->id,
                'class' => $class->name,
                'grade_level_id' => $class->grade_level_id ?? $student->grade_level_id,
                'stream_id' => $streamId,
                'house_id' => $houseId,
            ]);

            $this->syncClassEnrollmentCount($class->id);
            if ($previousClassId > 0 && $previousClassId !== (int) $class->id) {
                $this->syncClassEnrollmentCount($previousClassId);
            }

            if ($applyFees) {
                $this->ledgerService->applyClassFeeStructures(
                    student: $student->fresh(),
                    classId: $class->id,
                    contextLabel: $reason === 'admission' ? 'Admission fees' : 'Class transfer fee adjustment',
                    createdBy: $actorId,
                );
            }

            return $student->fresh(['classModel.teacher', 'stream', 'house', 'gradeLevel']);
        });
    }

    /**
     * Subjects expected for a student's grade (+ optional stream).
     *
     * @return array<int, array<string, mixed>>
     */
    public function subjectPackageFor(Student $student): array
    {
        if (! $student->grade_level_id) {
            return [];
        }

        return GradeLevelSubject::query()
            ->where('school_id', $student->school_id)
            ->where('grade_level_id', $student->grade_level_id)
            ->where(function ($q) use ($student) {
                $q->whereNull('stream_id');
                if ($student->stream_id) {
                    $q->orWhere('stream_id', $student->stream_id);
                }
            })
            ->with('subject:id,name,code')
            ->orderByDesc('is_core')
            ->get()
            ->map(fn (GradeLevelSubject $row) => [
                'id' => $row->id,
                'subject_id' => $row->subject_id,
                'subject' => $row->subject?->name,
                'code' => $row->subject?->code,
                'is_core' => (bool) $row->is_core,
                'stream_id' => $row->stream_id,
            ])
            ->values()
            ->all();
    }

    private function assertCapacity(ClassModel $class, Student $student): void
    {
        if (! $class->capacity) {
            return;
        }

        $count = Student::query()
            ->where('school_id', $class->school_id)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->when($student->id, fn ($q) => $q->where('id', '!=', $student->id))
            ->count();

        if ($count >= (int) $class->capacity) {
            throw DomainException::make(
                'classroom_capacity_exceeded',
                'Classroom capacity exceeded.',
                ['class_id' => ["{$class->name} is at full capacity ({$class->capacity})."]],
            );
        }
    }

    private function syncClassEnrollmentCount(int $classId): void
    {
        if ($classId <= 0) {
            return;
        }

        $count = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->count();

        ClassModel::query()->where('id', $classId)->update(['current_enrollment' => $count]);
    }
}
