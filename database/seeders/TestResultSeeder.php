<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestResult;
use Illuminate\Database\Seeder;

class TestResultSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::all() as $school) {
            $tests = Test::where('school_id', $school->id)
                ->where('is_published', true)
                ->get();
            
            $students = Student::where('school_id', $school->id)
                ->where('status', 'active')
                ->get();

            if ($tests->isEmpty() || $students->isEmpty()) {
                continue;
            }

            foreach ($tests as $test) {
                // Get students in the same class
                $classStudents = $students->filter(function ($student) use ($test) {
                    return $student->class_id === $test->class_id;
                });

                if ($classStudents->isEmpty()) {
                    continue;
                }

                $studentsToAssign = $classStudents->take(
                    max(1, (int) floor($classStudents->count() * 0.75))
                );

                foreach ($studentsToAssign as $student) {
                    $marks = rand(5, (int)$test->total_marks);
                    $percentage = ($marks / $test->total_marks) * 100;
                    $grade = $this->getGrade($percentage);

                    TestResult::updateOrCreate(
                        [
                            'test_id' => $test->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'subject_id' => $test->subject_id,
                            'marks_obtained' => $marks,
                            'total_marks' => $test->total_marks,
                            'percentage' => $percentage,
                            'grade' => $grade,
                            'remarks' => $marks >= ($test->total_marks * 0.7) ? 'Good work' : ($marks >= ($test->total_marks * 0.5) ? 'Satisfactory' : 'Practice more'),
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
