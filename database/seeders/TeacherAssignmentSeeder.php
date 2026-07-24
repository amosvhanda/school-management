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
            $teachers = Teacher::where('school_id', $school->id)->orderBy('id')->get();
            $classes = ClassModel::where('school_id', $school->id)->orderBy('id')->get();
            $subjects = Subject::where('school_id', $school->id)->orderBy('id')->get();
            $gradeLevels = GradeLevel::where('school_id', $school->id)->get();

            if ($teachers->isEmpty() || $classes->isEmpty() || $subjects->isEmpty()) {
                continue;
            }

            // Assign class teachers (homeroom) — portal ownership uses class_id.
            foreach ($classes as $class) {
                if ($class->teacher_id) {
                    TeacherAssignment::updateOrCreate(
                        [
                            'school_id' => $school->id,
                            'teacher_id' => $class->teacher_id,
                            'class_id' => $class->id,
                            'subject_id' => null,
                        ],
                        [
                            'role' => 'class_teacher',
                            'assigned_at' => now(),
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Assign subject teachers to classes
            foreach ($classes as $class) {
                $classSubjects = $subjects->take(min(3, $subjects->count()));
                foreach ($classSubjects as $idx => $subject) {
                    $teacher = $teachers[$idx % $teachers->count()];
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

            // Form teachers (optional grade-level only rows)
            foreach ($gradeLevels as $i => $gradeLevel) {
                $teacher = $teachers[$i % $teachers->count()];
                TeacherAssignment::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'teacher_id' => $teacher->id,
                        'grade_level_id' => $gradeLevel->id,
                        'class_id' => null,
                        'subject_id' => null,
                    ],
                    [
                        'role' => 'form_teacher',
                        'assigned_at' => now(),
                        'is_active' => true,
                    ]
                );
            }

            // Demo teacher (teacher@school.co.zw): ensure rich class+subject coverage for the portal.
            $demoTeacherUser = User::query()->where('email', 'teacher@school.co.zw')->first();
            $demoTeacher = $demoTeacherUser
                ? Teacher::query()
                    ->where('school_id', $school->id)
                    ->where(function ($q) use ($demoTeacherUser) {
                        $q->where('user_id', $demoTeacherUser->id)
                            ->orWhere('email', $demoTeacherUser->email);
                    })
                    ->first()
                : null;

            if (! $demoTeacher) {
                continue;
            }

            // Prefer demo teacher as homeroom for the first two classes.
            foreach ($classes->take(2) as $class) {
                $class->update(['teacher_id' => $demoTeacher->id]);

                TeacherAssignment::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'teacher_id' => $demoTeacher->id,
                        'class_id' => $class->id,
                        'subject_id' => null,
                    ],
                    [
                        'role' => 'class_teacher',
                        'assigned_at' => now(),
                        'is_active' => true,
                    ]
                );

                foreach ($subjects->take(min(4, $subjects->count())) as $subject) {
                    TeacherAssignment::updateOrCreate(
                        [
                            'school_id' => $school->id,
                            'teacher_id' => $demoTeacher->id,
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
        }
    }
}
