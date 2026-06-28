<?php

namespace App\Services\Enterprise;

use App\Models\AcademicCalendarEntry;
use App\Models\ContinuousAssessment;
use App\Models\CurriculumVersion;
use App\Models\ExamResult;
use App\Models\GradebookRule;
use App\Models\LearningOutcomeRecord;
use App\Models\PromotionRule;
use App\Models\Student;
use App\Models\SubjectPrerequisite;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AcademicEnterpriseService
{
    public function publishCurriculum(int $schoolId, array $data): CurriculumVersion
    {
        $latest = CurriculumVersion::where('school_id', $schoolId)
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year', $data['academic_year'])
            ->max('version_number');

        return CurriculumVersion::create([
            'school_id' => $schoolId,
            'subject_id' => $data['subject_id'],
            'term_id' => $data['term_id'] ?? null,
            'academic_year' => $data['academic_year'],
            'version_number' => ((int) $latest) + 1,
            'syllabus' => $data['syllabus'] ?? null,
            'learning_outcomes' => $data['learning_outcomes'] ?? [],
            'status' => $data['status'] ?? 'published',
            'published_by' => Auth::id(),
            'published_at' => now(),
        ]);
    }

    public function recordOutcome(int $schoolId, array $data): LearningOutcomeRecord
    {
        return LearningOutcomeRecord::create(array_merge($data, ['school_id' => $schoolId]));
    }

    public function recordContinuousAssessment(int $schoolId, array $data): ContinuousAssessment
    {
        return ContinuousAssessment::create(array_merge($data, ['school_id' => $schoolId]));
    }

    public function calculateWeightedGrade(int $studentId, int $subjectId, ?int $termId = null): array
    {
        $student = Student::findOrFail($studentId);
        $rule = GradebookRule::where('school_id', $student->school_id)
            ->where(function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId)->orWhereNull('subject_id');
            })
            ->orderByDesc('subject_id')
            ->first();

        $weights = $rule?->category_weights ?? [];
        $assessments = ContinuousAssessment::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->with('category')
            ->get();

        $byCategory = $assessments->groupBy('category_id');
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($byCategory as $categoryId => $rows) {
            $category = $rows->first()->category;
            $weight = (float) ($weights[$categoryId] ?? $weights[$category?->type] ?? $category?->weight ?? 0);
            if ($weight <= 0) {
                continue;
            }
            $avg = $rows->avg(fn ($r) => $r->max_score > 0 ? ($r->score / $r->max_score) * 100 : 0);
            $weightedSum += $avg * $weight;
            $totalWeight += $weight;
        }

        $finalPercent = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;

        return [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'weighted_percent' => $finalPercent,
            'pass' => $finalPercent >= (float) ($rule?->pass_mark ?? 50),
        ];
    }

    public function calculateGpaAndRank(int $schoolId, ?int $gradeLevelId = null): Collection
    {
        $students = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($gradeLevelId, fn ($q) => $q->where('grade_level_id', $gradeLevelId))
            ->get();

        $scores = $students->map(function (Student $student) {
            $results = ExamResult::where('student_id', $student->id)->get();
            $avg = $results->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0) ?: 0;
            $gpa = round(($avg / 100) * 4, 2);

            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'average_percent' => round($avg, 2),
                'gpa' => $gpa,
            ];
        })->sortByDesc('gpa')->values();

        return $scores->map(function ($row, $index) {
            $row['rank'] = $index + 1;

            return $row;
        });
    }

    public function checkPrerequisites(int $studentId, int $subjectId): array
    {
        $student = Student::findOrFail($studentId);
        $prereqs = SubjectPrerequisite::where('school_id', $student->school_id)
            ->where('subject_id', $subjectId)
            ->get();

        $failures = [];
        foreach ($prereqs as $prereq) {
            $result = ExamResult::where('student_id', $studentId)
                ->where('subject_id', $prereq->prerequisite_subject_id)
                ->orderByDesc('created_at')
                ->first();

            $percent = $result && $result->total_marks > 0
                ? ($result->marks_obtained / $result->total_marks) * 100
                : 0;

            if ($percent < (float) $prereq->minimum_grade) {
                $failures[] = [
                    'prerequisite_subject_id' => $prereq->prerequisite_subject_id,
                    'required_grade' => $prereq->minimum_grade,
                    'achieved_percent' => round($percent, 2),
                ];
            }
        }

        return ['eligible' => count($failures) === 0, 'failures' => $failures];
    }

    public function evaluatePromotionRules(int $schoolId, int $studentId): ?string
    {
        $student = Student::findOrFail($studentId);
        $rules = PromotionRule::where('school_id', $schoolId)->where('is_active', true)->get();
        $avg = ExamResult::where('student_id', $studentId)
            ->get()
            ->avg(fn ($r) => $r->total_marks > 0 ? ($r->marks_obtained / $r->total_marks) * 100 : 0) ?? 0;

        foreach ($rules as $rule) {
            $conditions = $rule->conditions ?? [];
            $minAvg = (float) ($conditions['min_average'] ?? 50);
            $maxFailures = (int) ($conditions['max_failed_subjects'] ?? 2);

            if ($avg >= $minAvg) {
                return $rule->action;
            }
            if ($avg < $minAvg && $maxFailures > 0) {
                return 'repeat';
            }
        }

        return null;
    }

    public function calendar(int $schoolId, ?string $year = null)
    {
        return AcademicCalendarEntry::where('school_id', $schoolId)
            ->when($year, fn ($q) => $q->whereYear('start_date', $year))
            ->orderBy('start_date')
            ->get();
    }
}
