<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::all() as $school) {
            $teachers = Teacher::where('school_id', $school->id)->get();
            $classes = ClassModel::where('school_id', $school->id)->get();
            $subjects = Subject::where('school_id', $school->id)->get();
            $gradeLevels = GradeLevel::where('school_id', $school->id)->get();

            if ($teachers->isEmpty() || $classes->isEmpty() || $subjects->isEmpty()) {
                continue;
            }

            // Assign class teachers
            foreach ($classes as $class) {
                if ($class->teacher_id) {
                    $teacher = Teacher::find($class->teacher_id);
                    if ($teacher) {
                        TeacherAssignment::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'teacher_id' => $teacher->id,
                                'class_id' => $class->id,
                            ],
                            [
                                'role' => 'class_teacher',
                                'assigned_at' => now(),
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }

            // Assign subject teachers to classes
            foreach ($classes as $class) {
                $classSubjects = $subjects->random(min(3, $subjects->count()));
                foreach ($classSubjects as $subject) {
                    $teacher = $teachers->random();
                    TeacherAssignment::updateOrCreate(
                        [
                            'school_id' => $school->id,
                            'teacher_id' => $teacher->id,
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                        ],
                        [
                            'role' => 'subject_teacher',
                            'assigned_at' => now(),
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Assign form teachers to grade levels
            foreach ($gradeLevels as $gradeLevel) {
                if (rand(1, 100) <= 70) { // 70% chance
                    $teacher = $teachers->random();
                    TeacherAssignment::updateOrCreate(
                        [
                            'school_id' => $school->id,
                            'teacher_id' => $teacher->id,
                            'grade_level_id' => $gradeLevel->id,
                        ],
                        [
                            'role' => 'form_teacher',
                            'assigned_at' => now(),
                            'is_active' => true,
                        ]
                    );
                }
            }

            $demoTeacherUser = User::query()->where('email', 'teacher@school.co.zw')->first();
            $demoTeacher = $demoTeacherUser
                ? Teacher::query()->where('school_id', $school->id)->where('user_id', $demoTeacherUser->id)->first()
                : null;

            if ($demoTeacher) {
                foreach ($subjects->take(4) as $subject) {
                    foreach ($gradeLevels as $gradeLevel) {
                        TeacherAssignment::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'teacher_id' => $demoTeacher->id,
                                'subject_id' => $subject->id,
                                'grade_level_id' => $gradeLevel->id,
                            ],
                            [
                                'role' => 'subject_teacher',
                                'assigned_at' => now(),
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        }
    }
}
