<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::all();
        if ($schools->isEmpty()) {
            return;
        }

        $teacherUser = User::where('email', 'teacher@school.co.zw')->first();
        $subjects = ZimbabweData::SUBJECTS;
        $depts = ['Sciences', 'Languages', 'Humanities'];

        foreach ($schools as $school) {
            for ($i = 0; $i < 5; $i++) {
                $emp = $school->code . '-EMP' . str_pad($school->id * 10 + $i + 1, 4, '0', STR_PAD_LEFT);
                $fn = ZimbabweData::firstName();
                $sn = ZimbabweData::surname();
                $name = "{$fn} {$sn}";
                $email = 'teacher' . ($school->id * 10 + $i + 1) . '@' . strtolower($school->code) . '.school.co.zw';
                
                // Link teacher user for first school, first teacher only
                $user = null;
                if ($i === 0 && $school->id === $teacherUser?->school_id) {
                    // Verify names match
                    if ($teacherUser->first_name === 'Tinashe' && $teacherUser->last_name === 'Moyo') {
                        $user = $teacherUser;
                        // Use teacher user's name
                        $fn = $teacherUser->first_name;
                        $sn = $teacherUser->last_name;
                        $name = $teacherUser->name;
                        $email = $teacherUser->email;
                    }
                }
                
                Teacher::updateOrCreate(
                    ['employee_id' => $emp],
                    [
                        'email' => $email,
                        'first_name' => $fn,
                        'last_name' => $sn,
                        'name' => $name,
                        'phone' => ZimbabweData::phone(),
                        'subject' => $subjects[$i % count($subjects)],
                        'department' => $depts[$i % 3],
                        'qualification' => 'BSc Education',
                        'joining_date' => now()->subYears(rand(1, 5))->format('Y-m-d'),
                        'status' => 'active',
                        'school_id' => $school->id,
                        'user_id' => $user?->id,
                    ]
                );
            }
        }
    }
}
