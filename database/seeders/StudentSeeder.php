<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\Helpers\SeederRelations;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::all();
        if ($schools->isEmpty()) {
            return;
        }

        $studentUser = User::where('email', 'student@school.co.zw')->first();
        $classes = ZimbabweData::CLASS_NAMES;

        foreach ($schools as $school) {
            $schoolClasses = ClassModel::where('school_id', $school->id)->get()->keyBy('name');
            if ($schoolClasses->isEmpty()) {
                continue;
            }
            $classNames = $schoolClasses->keys()->toArray();
            $base = [$school->code, date('Y')];

            $fixed = [
                ['fn' => 'Nyasha', 'sn' => 'Chiremba', 'class' => 'Form 3B', 'link_user' => true],
                ['fn' => 'Tinashe', 'sn' => 'Moyo', 'class' => 'Form 4A', 'link_user' => false],
                ['fn' => 'Ruvimbo', 'sn' => 'Gondo', 'class' => 'Form 2B', 'link_user' => false],
                ['fn' => 'Tafadzwa', 'sn' => 'Nyamande', 'class' => 'Form 1A', 'link_user' => false],
                ['fn' => 'Chipo', 'sn' => 'Chigova', 'class' => 'Grade 7', 'link_user' => false],
            ];

            foreach ($fixed as $idx => $s) {
                if (!in_array($s['class'], $classNames, true)) {
                    continue;
                }
                $sn = implode('-', $base) . '-F' . ($idx + 1);
                $fullName = $s['fn'] . ' ' . $s['sn'];
                
                // Link user for first school only, and only if it's the student user
                $user = null;
                if ($s['link_user'] && $studentUser && $studentUser->school_id === $school->id) {
                    // Verify the names match
                    if ($studentUser->first_name === $s['fn'] && $studentUser->last_name === $s['sn']) {
                        $user = $studentUser;
                    }
                }
                
                $classModel = $schoolClasses->get($s['class']);
                $gradeLevelId = SeederRelations::resolveGradeLevelId($school, $s['class']);
                Student::updateOrCreate(
                    ['student_number' => $sn],
                    [
                        'first_name' => $s['fn'],
                        'last_name' => $s['sn'],
                        'full_name' => $fullName,
                        'date_of_birth' => (string) (2005 + rand(0, 6)) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                        'gender' => rand(0, 1) ? 'male' : 'female',
                        'phone' => ZimbabweData::phone(),
                        'email' => strtolower($s['fn'] . '.' . $s['sn']) . '@' . strtolower($school->code) . '.school.co.zw',
                        'address' => 'Harare, Zimbabwe',
                        'suburb' => ['Kuwadzana', 'Mufakose', 'Glen View', 'Dzivarasekwa'][array_rand(['Kuwadzana', 'Mufakose', 'Glen View', 'Dzivarasekwa'])],
                        'class' => $s['class'],
                        'school' => $school->name,
                        'status' => 'active',
                        'balance' => 0,
                        'currency' => 'USD',
                        'school_id' => $school->id,
                        'class_id' => $classModel?->id,
                        'grade_level_id' => $gradeLevelId,
                        'user_id' => $user?->id,
                        'guardian_first_name' => ZimbabweData::firstName(),
                        'guardian_last_name' => ZimbabweData::surname(),
                        'guardian_phone' => ZimbabweData::phone(),
                        'guardian_relationship' => 'parent',
                    ]
                );
            }

            for ($i = 0; $i < 25; $i++) {
                $studentNum = implode('-', $base) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
                $fn = ZimbabweData::firstName();
                $surname = ZimbabweData::surname();
                $className = $classNames[$i % count($classNames)];
                $classModel = $schoolClasses->get($className);
                $gradeLevelId = SeederRelations::resolveGradeLevelId($school, $className);
                $fullName = "{$fn} {$surname}";
                Student::updateOrCreate(
                    ['student_number' => $studentNum],
                    [
                        'first_name' => $fn,
                        'last_name' => $surname,
                        'full_name' => $fullName,
                        'date_of_birth' => (string) (2005 + ($i % 7)) . '-' . str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT),
                        'gender' => $i % 2 === 0 ? 'male' : 'female',
                        'phone' => ZimbabweData::phone(),
                        'email' => strtolower($fn . '.' . $surname . $i) . '@' . strtolower($school->code) . '.school.co.zw',
                        'address' => 'Harare, Zimbabwe',
                        'suburb' => ['Kuwadzana', 'Mufakose', 'Glen View', 'Dzivarasekwa', 'Budiriro'][$i % 5],
                        'class' => $className,
                        'school' => $school->name,
                        'status' => $i % 10 === 0 ? 'inactive' : 'active',
                        'balance' => rand(0, 50) * 10,
                        'currency' => ZimbabweData::CURRENCIES[$i % 2],
                        'school_id' => $school->id,
                        'class_id' => $classModel?->id,
                        'grade_level_id' => $gradeLevelId,
                        'guardian_first_name' => ZimbabweData::firstName(),
                        'guardian_last_name' => ZimbabweData::surname(),
                        'guardian_phone' => ZimbabweData::phone(),
                        'guardian_relationship' => ['parent', 'mother', 'father', 'guardian'][$i % 4],
                    ]
                );
            }

            // Note: Enrollment relationships are created by EnrollmentSeeder
        }
    }
}
