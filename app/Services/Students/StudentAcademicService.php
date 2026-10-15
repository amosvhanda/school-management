<?php

namespace App\Services\Students;

use App\Models\Exam;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentAcademicService
{
    /**
     * @return array{
     *     student: Student,
     *     grades: Collection<int, mixed>,
     *     attendance: Collection<int, mixed>,
     *     average_score: float|int|null,
     *     total_grades: int
     * }
     */
    public function performanceSummary(Student $student): array
    {
        $student->load(['grades', 'attendance']);

        $grades = $student->grades;

        return [
            'student' => $student,
            'grades' => $grades,
            'attendance' => $student->attendance,
            'average_score' => $grades->avg('score'),
            'total_grades' => $grades->count(),
        ];
    }

    /**
     * Exams for the student's grade level, with optional learner publish filter.
     *
     * @return list<array<string, mixed>>
     */
    public function examsForStudent(Student $student, bool $viewerIsLearner): array
    {
        $examsQuery = Exam::query()
            ->where('school_id', $student->school_id)
            ->where('grade_level_id', $student->grade_level_id)
            ->with([
                'term',
                'gradeLevel',
                'subject',
                'examResults' => fn ($q) => $q->where('student_id', $student->id),
            ])
            ->orderByDesc('exam_date');

        if ($viewerIsLearner) {
            $examsQuery->where(function ($q) {
                $q->where('is_published', true)
                    ->orWhereNotNull('results_approved_at');
            });
        }

        return $examsQuery
            ->get()
            ->map(function (Exam $exam) use ($viewerIsLearner) {
                $result = $exam->examResults->first();

                $showResult = $result && (
                    ! $viewerIsLearner
                    || $exam->is_published
                    || $exam->results_approved_at
                );

                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'description' => $exam->description,
                    'subject' => $exam->subject->name ?? null,
                    'exam_date' => $exam->exam_date,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,
                    'total_marks' => $exam->total_marks,
                    'passing_marks' => $exam->passing_marks,
                    'term' => $exam->term->name ?? null,
                    'academic_year' => $exam->academic_year,
                    'is_published' => (bool) $exam->is_published,
                    'result' => $showResult
                        ? [
                            'marks_obtained' => $result->marks_obtained,
                            'percentage' => $result->percentage,
                            'grade' => $result->grade,
                            'remarks' => $result->remarks,
                        ]
                        : null,
                ];
            })
            ->all();
    }
}
