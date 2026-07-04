<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ExamResultSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::all() as $school) {
            $exams = Exam::where('school_id', $school->id)->get();

            $students = Student::where('school_id', $school->id)
                ->where('status', 'active')
                ->get();

            if ($exams->isEmpty() || $students->isEmpty()) {
                continue;
            }

            foreach ($exams as $exam) {
                // Get students in the same grade level
                $gradeLevelStudents = $students->take(min(20, $students->count()));

                foreach ($gradeLevelStudents as $student) {
                    $marks = rand(40, 95);
                    $percentage = ($marks / $exam->total_marks) * 100;
                    $grade = $this->getGrade($percentage);

                    ExamResult::updateOrCreate(
                        [
                            'exam_id' => $exam->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'subject_id' => $exam->subject_id,
                            'marks_obtained' => $marks,
                            'total_marks' => $exam->total_marks,
                            'percentage' => $percentage,
                            'grade' => $grade,
                            'remarks' => $marks >= 70 ? 'Good performance' : ($marks >= 50 ? 'Satisfactory' : 'Needs improvement'),
                        ]
                    );
                }
            }
        }
    }

    private function getGrade(float $percentage): string
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 75) return 'B+';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 65) return 'C+';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        if ($percentage >= 40) return 'E';
        return 'F';
    }
}
