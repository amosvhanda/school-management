<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Create enrollment records linking students to classes.
     * This establishes the many-to-many relationship via enrollments table.
     */
    public function run(): void
    {
        $academicYear = (string) now()->year;

        foreach (School::all() as $school) {
            $students = Student::where('school_id', $school->id)
                ->where('status', 'active')
                ->get();

            $classes = ClassModel::where('school_id', $school->id)->get()->keyBy('name');

            foreach ($students as $student) {
                // Find the class for this student
                $className = $student->class;
                $classModel = $classes->get($className);

                if (!$classModel) {
                    // Try to find by class_id if set
                    if ($student->class_id) {
                        $classModel = ClassModel::find($student->class_id);
                    }
                }

                if (!$classModel) {
                    continue; // Skip if no class found
                }

                // Create enrollment record
                Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'class_id' => $classModel->id,
                        'academic_year' => $academicYear,
                    ],
                    [
                        'school_id' => $school->id,
                        'enrolled_at' => now()->subMonths(rand(1, 6)),
                        'status' => 'active',
                    ]
                );

                // Update student's class_id if not set
                if (!$student->class_id) {
                    $student->class_id = $classModel->id;
                }

                if ($classModel->grade_level_id && $student->grade_level_id !== $classModel->grade_level_id) {
                    $student->grade_level_id = $classModel->grade_level_id;
                }

                if ($student->isDirty(['class_id', 'grade_level_id'])) {
                    $student->save();
                }
            }

            // Update class enrollment counts
            foreach ($classes as $class) {
                $enrollmentCount = Enrollment::where('class_id', $class->id)
                    ->where('status', 'active')
                    ->where('academic_year', $academicYear)
                    ->count();
                $class->current_enrollment = $enrollmentCount;
                $class->save();
            }
        }
    }
}
