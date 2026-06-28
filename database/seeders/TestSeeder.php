<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\Test;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = date('Y');
        $academicYear = "{$currentYear}-" . ($currentYear + 1);

        foreach (School::all() as $school) {
            $terms = Term::where('school_id', $school->id)
                ->where('academic_year', $academicYear)
                ->get();
            
            $classes = ClassModel::where('school_id', $school->id)->get();
            $subjects = Subject::where('school_id', $school->id)->get();
            $teachers = Teacher::where('school_id', $school->id)->get();

            if ($terms->isEmpty() || $classes->isEmpty() || $subjects->isEmpty() || $teachers->isEmpty()) {
                continue;
            }

            $testTypes = ['Chapter Test', 'Quiz', 'Assignment', 'Class Test', 'Unit Test'];

            foreach ($classes as $class) {
                $classSubjects = $subjects->take(min(3, $subjects->count()));
                $classTeacher = $teachers[$class->id % $teachers->count()];
                $termsList = $terms->values();

                foreach ($classSubjects as $subjectIndex => $subject) {
                    for ($i = 0; $i < 2; $i++) {
                        $seed = $class->id + $subject->id + $i;
                        $term = $termsList[$seed % $termsList->count()];
                        $testType = $testTypes[($seed + $subjectIndex) % count($testTypes)];
                        $testDate = date('Y-m-d', strtotime($term->start_date.' + '.(10 + ($seed % 70)).' days'));

                        Test::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'class_id' => $class->id,
                                'subject_id' => $subject->id,
                                'term_id' => $term->id,
                                'name' => "{$testType} - {$subject->name}",
                            ],
                            [
                                'teacher_id' => $classTeacher->id,
                                'description' => "{$testType} for {$subject->name} - {$class->name}",
                                'test_date' => $testDate,
                                'start_time' => '08:00:00',
                                'end_time' => '09:00:00',
                                'total_marks' => 20 + ($seed % 31),
                                'passing_marks' => 10 + ($seed % 16),
                                'academic_year' => $academicYear,
                                'is_published' => ($seed % 2) === 0,
                            ]
                        );
                    }
                }
            }
        }
    }
}
