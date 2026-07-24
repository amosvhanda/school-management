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
                $emp = $school->code.'-EMP'.str_pad($school->id * 10 + $i + 1, 4, '0', STR_PAD_LEFT);
                $fn = ZimbabweData::firstName();
                $sn = ZimbabweData::surname();
                $name = "{$fn} {$sn}";
                $email = 'teacher'.($school->id * 10 + $i + 1).'@'.strtolower($school->code).'.school.co.zw';

                // Always link the demo login to the first teacher at the primary school.
                $userId = null;
                if ($i === 0 && $teacherUser && (int) $teacherUser->school_id === (int) $school->id) {
                    $userId = $teacherUser->id;
                    $fn = $teacherUser->first_name ?: 'Tinashe';
                    $sn = $teacherUser->last_name ?: 'Moyo';
                    $name = $teacherUser->name ?: "{$fn} {$sn}";
                    $email = $teacherUser->email;
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
                        'employment_type' => 'permanent',
                        'joining_date' => now()->subYears(rand(1, 5))->format('Y-m-d'),
                        'status' => 'active',
                        'school_id' => $school->id,
                        'user_id' => $userId,
                    ]
                );
            }

            // Safety net: if the demo user exists for this school, ensure a Teacher row is linked.
            if ($teacherUser && (int) $teacherUser->school_id === (int) $school->id) {
                $linked = Teacher::query()
                    ->where('school_id', $school->id)
                    ->where('user_id', $teacherUser->id)
                    ->first();

                if (! $linked) {
                    $first = Teacher::query()->where('school_id', $school->id)->orderBy('id')->first();
                    if ($first) {
                        $first->update([
                            'user_id' => $teacherUser->id,
                            'email' => $teacherUser->email,
                            'first_name' => $teacherUser->first_name ?: $first->first_name,
                            'last_name' => $teacherUser->last_name ?: $first->last_name,
                            'name' => $teacherUser->name ?: $first->name,
                        ]);
                    }
                }
            }
        }
    }
}
