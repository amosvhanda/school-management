<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Services\Domain\SchoolDomainRules;
use Illuminate\Support\Facades\DB;

class StudentPromotionService
{
    public function __construct(
        private FinancialLedgerService $ledgerService,
        private SchoolDomainRules $domainRules,
    ) {}

    /**
     * Run year-end promotion for a school using grade_level order (practical progression).
     *
     * @return array{promoted: int, repeated: int, graduated: int, errors: array<int, string>}
     */
    public function runYearEnd(
        int $schoolId,
        string $academicYear,
        string $nextAcademicYear,
        float $passMark = 50.0,
        ?array $studentIds = null,
        ?int $processedBy = null,
    ): array {
        $promoted = 0;
        $repeated = 0;
        $graduated = 0;
        $errors = [];

        $query = Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active');

        if ($studentIds !== null) {
            $query->whereIn('id', $studentIds);
        }

        $students = $query->get();

        foreach ($students as $student) {
            try {
                $result = $this->processStudent(
                    $student,
                    $academicYear,
                    $nextAcademicYear,
                    $passMark,
                    $processedBy,
                );

                match ($result) {
                    'promoted' => $promoted++,
                    'repeated' => $repeated++,
                    'graduated' => $graduated++,
                    default => null,
                };
            } catch (\Throwable $e) {
                $errors[] = "Student {$student->full_name}: {$e->getMessage()}";
            }
        }

        return compact('promoted', 'repeated', 'graduated', 'errors');
    }

    private function processStudent(
        Student $student,
        string $academicYear,
        string $nextAcademicYear,
        float $passMark,
        ?int $processedBy,
    ): string {
        $year = (int) $academicYear;
        $this->domainRules->assertCoreResultsComplete($student, $year);

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->where('year', $year)
            ->get();

        if ($grades->isEmpty()) {
            throw DomainException::make(
                'incomplete_results',
                'Student cannot be promoted due to incomplete results.',
                ['results' => ["No grades recorded for {$academicYear}."]],
            );
        }

        $average = $grades->avg(fn ($g) => $g->total > 0 ? ($g->score / $g->total) * 100 : 0);

        return DB::transaction(function () use ($student, $average, $passMark, $nextAcademicYear, $processedBy, $year) {
            $this->closeActiveEnrollments($student, $nextAcademicYear);

            if ($average >= $passMark) {
                return $this->promoteStudent($student, $nextAcademicYear, $processedBy, $year);
            }

            return $this->repeatStudent($student, $nextAcademicYear, $processedBy);
        });
    }

    private function promoteStudent(Student $student, string $nextAcademicYear, ?int $processedBy, int $completedYear): string
    {
        if (! $student->grade_level_id) {
            throw DomainException::make(
                'incomplete_results',
                'Student cannot be promoted due to incomplete results.',
                ['grade_level_id' => ['Student has no grade level assigned.']],
            );
        }

        $currentLevel = GradeLevel::find($student->grade_level_id);
        $nextLevel = GradeLevel::query()
            ->where('school_id', $student->school_id)
            ->where('order', '>', $currentLevel?->order ?? 0)
            ->orderBy('order')
            ->first();

        if (! $nextLevel) {
            $this->domainRules->assertGraduationRequirements($student, $completedYear);

            $student->update(['status' => 'graduated']);
            Enrollment::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'academic_year' => $nextAcademicYear,
                'enrolled_at' => now(),
                'status' => 'graduated',
            ]);

            return 'graduated';
        }

        $nextClass = ClassModel::query()
            ->where('school_id', $student->school_id)
            ->where('grade_level_id', $nextLevel->id)
            ->orderBy('name')
            ->first();

        $student->update([
            'grade_level_id' => $nextLevel->id,
            'class_id' => $nextClass?->id,
            'class' => $nextClass?->name,
        ]);

        if ($nextClass) {
            Enrollment::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'class_id' => $nextClass->id,
                'academic_year' => $nextAcademicYear,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);
        }

        $this->ledgerService->applyClassFeeStructures(
            student: $student->fresh(),
            classId: $student->class_id,
            contextLabel: "Promotion fees {$nextAcademicYear}",
            createdBy: $processedBy,
        );

        return 'promoted';
    }

    private function repeatStudent(Student $student, string $nextAcademicYear, ?int $processedBy): string
    {
        $student->update([
            'repetition_count' => ($student->repetition_count ?? 0) + 1,
        ]);

        if ($student->class_id) {
            Enrollment::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'academic_year' => $nextAcademicYear,
                'enrolled_at' => now(),
                'status' => 'repeating',
            ]);
        }

        $this->ledgerService->applyClassFeeStructures(
            student: $student->fresh(),
            classId: $student->class_id,
            contextLabel: "Repeat fees {$nextAcademicYear}",
            createdBy: $processedBy,
        );

        return 'repeated';
    }

    private function closeActiveEnrollments(Student $student, string $nextAcademicYear): void
    {
        Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'repeating'])
            ->where('academic_year', '!=', $nextAcademicYear)
            ->update([
                'status' => 'completed',
                'left_at' => now(),
            ]);
    }
}
