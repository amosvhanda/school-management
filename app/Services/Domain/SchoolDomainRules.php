<?php

namespace App\Services\Domain;

use App\Exceptions\DomainException;
use App\Models\Enrollment;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\GradeLevelSubject;
use App\Models\HostelBed;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Models\StudentTransportAllocation;
use App\Models\TransportRoute;
use Illuminate\Support\Collection;

/**
 * Central School ERP domain guards — stable error_code values for API clients.
 */
class SchoolDomainRules
{
    public function assertStudentActive(Student $student): void
    {
        $status = strtolower((string) ($student->status ?? 'active'));

        if (in_array($status, ['inactive', 'withdrawn', 'transferred', 'expelled', 'graduated'], true)) {
            $name = trim((string) ($student->full_name ?: $student->student_number)) ?: ('#'.$student->id);
            throw DomainException::make(
                'student_inactive',
                "{$name}'s account is not active.",
                ['student' => ["{$name}'s account is already {$status}."]],
            );
        }
    }

    /**
     * Statuses that should not appear on day-to-day class registers.
     *
     * @return list<string>
     */
    public static function inactiveStudentStatuses(): array
    {
        return ['inactive', 'withdrawn', 'transferred', 'expelled', 'graduated'];
    }

    public function assertAdmissionNumberAvailable(int $schoolId, string $studentNumber, ?int $ignoreStudentId = null): void
    {
        $exists = Student::query()
            ->where('school_id', $schoolId)
            ->where('student_number', $studentNumber)
            ->when($ignoreStudentId, fn ($q) => $q->where('id', '!=', $ignoreStudentId))
            ->exists();

        if ($exists) {
            throw DomainException::make(
                'admission_number_exists',
                'Admission number already exists.',
                ['student_number' => ['Admission number already exists for this school.']],
            );
        }
    }

    public function assertNoOtherActiveClass(Student $student, int $targetClassId): void
    {
        $other = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'repeating'])
            ->where('class_id', '!=', $targetClassId)
            ->with('classModel:id,name')
            ->first();

        if ($other) {
            $className = $other->classModel?->name ?? ('#'.$other->class_id);
            throw DomainException::make(
                'already_enrolled_other_class',
                'Student already enrolled in another class.',
                ['class_id' => ["Student is already enrolled in {$className}. Transfer or complete that enrollment first."]],
            );
        }
    }

    public function assertSingleActiveEnrollment(Student $student): void
    {
        $count = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'repeating'])
            ->count();

        if ($count > 1) {
            throw DomainException::make(
                'multiple_active_classes',
                'Student is assigned to multiple active classes.',
                ['enrollment' => ['Student has more than one active class enrollment. Resolve duplicates before continuing.']],
            );
        }
    }

    public function assertNoOutstandingFees(Student $student, string $context = 'transfer'): void
    {
        $balance = (float) ($student->balance ?? 0);

        if ($balance > 0.009) {
            $currency = $student->currency ?? 'USD';
            throw DomainException::make(
                'fees_outstanding',
                "Student cannot {$context} because fees are outstanding.",
                ['balance' => ["Outstanding balance of {$currency} ".number_format($balance, 2).' must be cleared first.']],
            );
        }
    }

    public function assertSubjectAvailableForGrade(int $schoolId, int $gradeLevelId, int $subjectId): void
    {
        $packageExists = GradeLevelSubject::query()
            ->where('school_id', $schoolId)
            ->where('grade_level_id', $gradeLevelId)
            ->exists();

        // If the school has not defined a package for this grade, skip (legacy schools).
        if (! $packageExists) {
            return;
        }

        $allowed = GradeLevelSubject::query()
            ->where('school_id', $schoolId)
            ->where('grade_level_id', $gradeLevelId)
            ->where('subject_id', $subjectId)
            ->exists();

        if (! $allowed) {
            throw DomainException::make(
                'subject_not_in_grade',
                'Subject is not available for the selected grade.',
                ['subject_id' => ['This subject is not part of the subject package for the selected grade.']],
            );
        }
    }

    public function assertCoreResultsComplete(Student $student, int $academicYear): void
    {
        if (! $student->grade_level_id) {
            return;
        }

        $coreSubjectIds = GradeLevelSubject::query()
            ->where('school_id', $student->school_id)
            ->where('grade_level_id', $student->grade_level_id)
            ->where('is_core', true)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        if ($coreSubjectIds->isEmpty()) {
            $grades = Grade::query()
                ->where('student_id', $student->id)
                ->where('year', $academicYear)
                ->count();

            if ($grades === 0) {
                throw DomainException::make(
                    'incomplete_results',
                    'Student cannot be promoted due to incomplete results.',
                    ['results' => ["No grades recorded for {$academicYear}."]],
                );
            }

            return;
        }

        $gradedSubjectIds = Grade::query()
            ->where('student_id', $student->id)
            ->where('year', $academicYear)
            ->whereIn('subject_id', $coreSubjectIds)
            ->pluck('subject_id')
            ->unique();

        // Fallback: grades may store subject name only
        $gradedByName = Grade::query()
            ->where('student_id', $student->id)
            ->where('year', $academicYear)
            ->whereNotNull('subject')
            ->pluck('subject');

        $examSubjectIds = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('academic_year', (string) $academicYear))
            ->pluck('subject_id')
            ->unique();

        $covered = $gradedSubjectIds->merge($examSubjectIds)->unique()->filter();

        $missing = $coreSubjectIds->diff($covered);

        if ($missing->isNotEmpty() && $gradedByName->isEmpty()) {
            throw DomainException::make(
                'incomplete_results',
                'Student cannot be promoted due to incomplete results.',
                ['results' => ['Core subject results are incomplete for '.$academicYear.'.']],
            );
        }

        if ($missing->isNotEmpty() && $gradedByName->isNotEmpty()) {
            // Name-only grades: require at least as many grade rows as core subjects.
            $gradeCount = Grade::query()
                ->where('student_id', $student->id)
                ->where('year', $academicYear)
                ->count();

            if ($gradeCount < $coreSubjectIds->count()) {
                throw DomainException::make(
                    'incomplete_results',
                    'Student cannot be promoted due to incomplete results.',
                    ['results' => ['Not all core subject results are recorded for '.$academicYear.'.']],
                );
            }
        }
    }

    public function assertGraduationRequirements(Student $student, int $academicYear): void
    {
        $this->assertCoreResultsComplete($student, $academicYear);

        if (! $student->grade_level_id) {
            throw DomainException::make(
                'credits_missing',
                'Student cannot graduate because required credits are missing.',
                ['grade_level_id' => ['Student has no grade level assigned.']],
            );
        }

        $coreCount = GradeLevelSubject::query()
            ->where('school_id', $student->school_id)
            ->where('grade_level_id', $student->grade_level_id)
            ->where('is_core', true)
            ->count();

        if ($coreCount === 0) {
            return;
        }

        $resultCount = Grade::query()
            ->where('student_id', $student->id)
            ->where('year', $academicYear)
            ->count();

        $examCount = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('academic_year', (string) $academicYear))
            ->count();

        if (($resultCount + $examCount) < $coreCount) {
            throw DomainException::make(
                'credits_missing',
                'Student cannot graduate because required credits are missing.',
                ['results' => ['Required core subject credits for this grade are incomplete.']],
            );
        }
    }

    /**
     * @param  Collection<int, mixed>  $grades
     * @param  Collection<int, mixed>  $examResults
     */
    public function assertReportCardHasGrades(Student $student, Collection $grades, Collection $examResults): void
    {
        if ($grades->isEmpty() && $examResults->isEmpty()) {
            throw DomainException::make(
                'report_card_missing_grades',
                'Report card generation failed due to missing grades.',
                ['results' => ['No grades or exam results are available for this student.']],
            );
        }
    }

    public function assertHostelRoomHasCapacity(HostelBed $bed): void
    {
        $room = $bed->relationLoaded('room')
            ? $bed->room
            : HostelRoom::query()->find($bed->room_id);

        if (! $room || ! $room->capacity) {
            return;
        }

        $occupied = HostelBed::query()
            ->where('room_id', $room->id)
            ->where('status', 'occupied')
            ->count();

        // Allocating this bed will occupy one more slot if currently available.
        $projected = $bed->status === 'available' ? $occupied + 1 : $occupied;

        if ($projected > (int) $room->capacity) {
            throw DomainException::make(
                'hostel_room_full',
                'Hostel room is full.',
                ['bed_id' => ['This hostel room has reached its capacity of '.$room->capacity.'.']],
            );
        }
    }

    public function assertTransportRouteHasCapacity(TransportRoute $route, ?int $ignoreStudentId = null): void
    {
        $route->loadMissing('vehicle');
        $capacity = $route->vehicle?->capacity;

        if (! $capacity) {
            return;
        }

        $active = StudentTransportAllocation::query()
            ->where('route_id', $route->id)
            ->where('status', 'active')
            ->when($ignoreStudentId, fn ($q) => $q->where('student_id', '!=', $ignoreStudentId))
            ->count();

        if ($active >= (int) $capacity) {
            throw DomainException::make(
                'transport_route_full',
                'Transport route has reached capacity.',
                ['route_id' => ['This route\'s vehicle is full (capacity '.$capacity.').']],
            );
        }
    }

    public function assertMarksWithinMaximum(float $marks, float $maximum): void
    {
        if ($marks > $maximum) {
            throw DomainException::make(
                'marks_exceed_maximum',
                'Exam marks exceed the maximum allowed.',
                ['marks_obtained' => ["Marks cannot exceed the maximum of {$maximum}."]],
            );
        }
    }
}
