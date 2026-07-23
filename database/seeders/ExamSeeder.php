<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = date('Y');
        $academicYear = "{$currentYear}-" . ($currentYear + 1);

        foreach (School::all() as $school) {
            $terms = Term::where('school_id', $school->id)
                ->where('academic_year', $academicYear)
                ->get();
            
            $gradeLevels = GradeLevel::where('school_id', $school->id)->get();
            $subjects = Subject::where('school_id', $school->id)->get();

            if ($terms->isEmpty() || $gradeLevels->isEmpty() || $subjects->isEmpty()) {
                continue;
            }

            $examTypes = ['Mid-Term Exam', 'Final Exam', 'End of Term Exam', 'Mock Exam'];

            foreach ($terms as $term) {
                foreach ($gradeLevels as $gradeLevel) {
                    foreach ($subjects->take(4) as $subject) {
                        $seed = $term->id + $gradeLevel->id + $subject->id;
                        $examType = $examTypes[$seed % count($examTypes)];
                        $examDate = date('Y-m-d', strtotime($term->start_date.' + '.(20 + ($seed % 40)).' days'));
                        
                        Exam::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'term_id' => $term->id,
                                'grade_level_id' => $gradeLevel->id,
                                'subject_id' => $subject->id,
                                'name' => "{$examType} - {$subject->name}",
                            ],
                            [
                                'description' => "{$examType} for {$subject->name} - {$gradeLevel->name}",
                                'exam_date' => $examDate,
                                'start_time' => '09:00:00',
                                'end_time' => '11:00:00',
                                'total_marks' => 100,
                                'passing_marks' => 50,
                                'academic_year' => $academicYear,
                                // Publish Mid-Term and Final so parent/student portals show results.
                                'is_published' => in_array($examType, ['Mid-Term Exam', 'Final Exam'], true),
                                'results_approved_at' => in_array($examType, ['Mid-Term Exam', 'Final Exam'], true)
                                    ? now()->subDays(3)
                                    : null,
                            ]
                        );
                    }
                }
            }
        }
    }
}
