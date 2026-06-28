<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParentStudentSeeder extends Seeder
{
    private static ?string $parentPasswordHash = null;

    /**
     * Create parent users and link them to students via parent_student pivot table.
     */
    public function run(): void
    {
        static::$parentPasswordHash ??= Hash::make('parent123');

        foreach (School::all() as $school) {
            // Get or create the fixed parent user for this school
            $fixedParent = User::where('email', 'parent@school.co.zw')
                ->where('school_id', $school->id)
                ->first();

            if (!$fixedParent && $school->id === School::first()->id) {
                // Create fixed parent for first school
                $fixedParent = User::updateOrCreate(
                    ['email' => 'parent@school.co.zw'],
                    [
                        'name' => 'Tatenda Moyo',
                        'password' => static::$parentPasswordHash,
                        'role' => 'parent',
                        'first_name' => 'Tatenda',
                        'last_name' => 'Moyo',
                        'phone' => ZimbabweData::phone(),
                        'school_id' => $school->id,
                    ]
                );
            }

            // Get students for this school
            $students = Student::where('school_id', $school->id)
                ->where('status', 'active')
                ->get();

            if ($students->isEmpty()) {
                continue;
            }

            // Link fixed parent to first 5 students
            if ($fixedParent) {
                $studentsToLink = $students->take(5);
                foreach ($studentsToLink as $i => $student) {
                    DB::table('parent_student')->updateOrInsert(
                        [
                            'parent_id' => $fixedParent->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'relationship' => $i === 0 ? 'parent' : 'guardian',
                            'is_primary' => $i === 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            // Create additional parent users and link them to students
            $studentsWithoutParents = $students->skip(5)->take(15);
            foreach ($studentsWithoutParents as $student) {
                if (DB::table('parent_student')
                    ->where('student_id', $student->id)
                    ->where('is_primary', true)
                    ->exists()) {
                    continue;
                }

                $parentEmail = 'parent.'.$student->id.'@'.strtolower($school->code).'.school.co.zw';

                $parent = User::updateOrCreate(
                    ['email' => $parentEmail],
                    [
                        'name' => $student->full_name.' Parent',
                        'password' => static::$parentPasswordHash,
                        'role' => 'parent',
                        'first_name' => explode(' ', $student->full_name)[0] ?? 'Parent',
                        'last_name' => explode(' ', $student->full_name)[1] ?? 'Guardian',
                        'phone' => ZimbabweData::phone(),
                        'school_id' => $school->id,
                    ]
                );

                // Link parent to student
                DB::table('parent_student')->updateOrInsert(
                    [
                        'parent_id' => $parent->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'school_id' => $school->id,
                        'relationship' => 'parent',
                        'is_primary' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Some students have a second parent (deterministic — even student IDs)
                if ($student->id % 5 === 0) {
                    $secondParentEmail = 'guardian.'.$student->id.'@'.strtolower($school->code).'.school.co.zw';

                    $secondParent = User::updateOrCreate(
                        ['email' => $secondParentEmail],
                        [
                            'name' => $student->full_name.' Guardian',
                            'password' => static::$parentPasswordHash,
                            'role' => 'parent',
                            'first_name' => 'Guardian',
                            'last_name' => explode(' ', $student->full_name)[1] ?? 'Account',
                            'phone' => ZimbabweData::phone(),
                            'school_id' => $school->id,
                        ]
                    );

                    DB::table('parent_student')->updateOrInsert(
                        [
                            'parent_id' => $secondParent->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'relationship' => $student->id % 2 === 0 ? 'mother' : 'father',
                            'is_primary' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
