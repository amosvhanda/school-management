<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $suburbs = ['Kuwadzana', 'Mufakose', 'Glen View', 'Dzivarasekwa', 'Budiriro'];
        $grades = ['Grade 7', 'Form 1', 'Form 2', 'Form 3', 'Form 4'];

        foreach (School::all() as $school) {
            for ($i = 0; $i < 5; $i++) {
                $fn = ZimbabweData::firstName();
                $sn = ZimbabweData::surname();
                $email = 'applicant' . ($school->id * 10 + $i + 1) . '@' . strtolower(preg_replace('/[^a-z0-9]/', '', $school->code)) . '.apply.zw';

                $statuses = ['pending', 'pending', 'pending', 'approved', 'rejected'];
                $status = $statuses[$i % count($statuses)];

                $address = fake()->streetAddress() . ', Harare, Zimbabwe';
                $guardianAddress = fake()->streetAddress() . ', Harare, Zimbabwe';

                $exists = DB::table('enrollment_applications')
                    ->where('school_id', $school->id)
                    ->where('email', $email)
                    ->exists();

                if ($exists) {
                    DB::table('enrollment_applications')
                        ->where('school_id', $school->id)
                        ->where('email', $email)
                        ->update([
                            'first_name' => $fn,
                            'surname' => $sn,
                            'address' => $address,
                            'guardian_first_name' => ZimbabweData::firstName(),
                            'guardian_surname' => ZimbabweData::surname(),
                            'guardian_address' => $guardianAddress,
                            'updated_at' => now(),
                        ]);
                    continue;
                }

                DB::table('enrollment_applications')->insert([
                    'school_id' => $school->id,
                    'first_name' => $fn,
                    'surname' => $sn,
                    'date_of_birth' => (string) (2015 + $i) . '-01-15',
                    'gender' => $i % 2 === 0 ? 'male' : 'female',
                    'national_id' => fake()->optional(0.3)->numerify('##-######-#'),
                    'address' => $address,
                    'suburb' => $suburbs[$i % count($suburbs)],
                    'phone' => ZimbabweData::phone(),
                    'email' => $email,
                    'previous_school' => $i > 0 ? 'Previous Primary' : null,
                    'grade_applying_for' => $grades[$i % count($grades)],
                    'academic_year' => (string) now()->year,
                    'guardian_first_name' => ZimbabweData::firstName(),
                    'guardian_surname' => ZimbabweData::surname(),
                    'guardian_relationship' => 'parent',
                    'guardian_phone' => ZimbabweData::phone(),
                    'guardian_email' => 'guardian' . $i . '@example.com',
                    'guardian_address' => $guardianAddress,
                    'guardian_employer' => fake()->optional(0.5)->company(),
                    'medical_conditions' => fake()->optional(0.1)->sentence(),
                    'allergies' => fake()->optional(0.1)->word(),
                    'emergency_contact' => ZimbabweData::firstName() . ' ' . ZimbabweData::surname(),
                    'emergency_phone' => ZimbabweData::phone(),
                    'birth_certificate' => true,
                    'report_cards' => true,
                    'medical_certificate' => false,
                    'passport_photo' => true,
                    'status' => $status,
                    'notes' => fake()->optional(0.2)->sentence(),
                    'reviewed_by' => in_array($status, ['approved', 'rejected'], true) ? $admin?->id : null,
                    'reviewed_at' => in_array($status, ['approved', 'rejected'], true) ? now() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
