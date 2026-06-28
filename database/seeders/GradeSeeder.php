<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    private const TERMS = ['Term 1', 'Term 2', 'Term 3'];
    private const TYPES = ['test', 'assignment', 'exam', 'project'];

    public function run(): void
    {
        $year = (int) date('Y');
        $subjects = array_slice(ZimbabweData::SUBJECTS, 0, 4);

        foreach (Student::where('status', 'active')->whereNotNull('school_id')->get() as $student) {
            // Get student's class
            $classModel = null;
            if ($student->class_id) {
                $classModel = ClassModel::with('teacher')->find($student->class_id);
            }
            if (! $classModel) {
                $classModel = ClassModel::with('teacher')
                    ->where('school_id', $student->school_id)
                    ->where('name', $student->class)
                    ->first();
            }

            if (!$classModel) {
                continue; // Skip if no class found
            }

            // Get class teacher or find a teacher for this school
            $teacher = $classModel->teacher ?? Teacher::where('school_id', $student->school_id)->first();

            if (!$teacher) {
                continue; // Skip if no teacher found
            }

            // Get subjects for this school
            $schoolSubjects = Subject::where('school_id', $student->school_id)->get();

            foreach ($subjects as $idx => $subjectName) {
                // Find subject model
                $subjectModel = $schoolSubjects->firstWhere('name', $subjectName);
                
                if (!$subjectModel) {
                    // Try to find by name directly
                    $subjectModel = Subject::where('school_id', $student->school_id)
                        ->where('name', $subjectName)
                        ->first();
                }

                if (!$subjectModel) {
                    continue; // Skip if subject not found
                }

                $term = self::TERMS[$idx % count(self::TERMS)];
                $score = rand(45, 95);
                $grade = $this->scoreToGrade($score);
                
                Grade::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $subjectModel->id,
                        'term' => $term,
                        'year' => $year,
                        'assessment_type' => self::TYPES[$idx % count(self::TYPES)],
                    ],
                    [
                        'subject' => $subjectName,
                        'assessment_type' => self::TYPES[$idx % count(self::TYPES)],
                        'score' => $score,
                        'total' => 100,
                        'grade' => $grade,
                        'class_id' => $classModel->id,
                        'teacher_id' => $teacher->id,
                        'remarks' => $score >= 70 ? 'Good progress' : ($score >= 50 ? 'Satisfactory' : 'Needs improvement'),
                        'school_id' => $student->school_id,
                    ]
                );
            }
        }
    }

    private function scoreToGrade(int $score): string
    {
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        if ($score >= 40) return 'E';
        return 'F';
    }
}
