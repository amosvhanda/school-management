<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use Database\Seeders\Helpers\SeederRelations;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class GuardianSeeder extends Seeder
{
    /**
     * Create guardians and link them to students via guardian_student pivot table.
     */
    public function run(): void
    {
        foreach (School::all() as $school) {
            $students = Student::where('school_id', $school->id)
                ->where('status', 'active')
                ->get();

            foreach ($students as $student) {
                if ($student->guardian_first_name && $student->guardian_last_name) {
                    SeederRelations::linkGuardianToStudent(
                        school: $school,
                        student: $student,
                        firstName: $student->guardian_first_name,
                        lastName: $student->guardian_last_name,
                        phone: $student->guardian_phone ?? ZimbabweData::phone(),
                        relationship: $student->guardian_relationship ?? 'parent',
                        isPrimary: true,
                    );
                }

                // Deterministic second guardian for ~30% of students (stable across reseeds).
                if ($student->id % 3 === 0) {
                    $firstName = ZimbabweData::FIRST_NAMES[$student->id % count(ZimbabweData::FIRST_NAMES)];
                    $lastName = ZimbabweData::SURNAMES[($student->id + 2) % count(ZimbabweData::SURNAMES)];

                    SeederRelations::linkGuardianToStudent(
                        school: $school,
                        student: $student,
                        firstName: $firstName,
                        lastName: $lastName,
                        phone: ZimbabweData::phone(),
                        relationship: ['mother', 'father', 'guardian', 'aunt', 'uncle'][$student->id % 5],
                        isPrimary: false,
                        email: str_replace('@', '.alt@', SeederRelations::guardianEmailForStudent($student, $firstName, $lastName)),
                    );
                }
            }

            SeederRelations::linkDemoParentAccount($school);
        }
    }
}
