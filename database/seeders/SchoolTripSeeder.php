<?php

namespace Database\Seeders;

use App\Models\SchoolTrip;
use App\Models\SchoolTripEnrollment;
use App\Models\Student;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;

class SchoolTripSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        foreach ($this->demoSchools() as $school) {
            $parent = $this->demoParentUser($school);
            $linkedStudentIds = $parent
                ? $parent->students()->pluck('students.id')
                : collect();

            $museum = SchoolTrip::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'National Heroes Acre & Museum Visit'],
                [
                    'destination' => 'Harare Museum District',
                    'trip_date' => now()->addWeeks(3)->toDateString(),
                    'return_date' => now()->addWeeks(3)->toDateString(),
                    'fee_amount' => 15.00,
                    'currency' => 'USD',
                    'capacity' => 40,
                    'is_active' => true,
                    'open_for_registration' => true,
                    'description' => 'Day trip for Form 1–3 learners. Packed lunch required.',
                ]
            );

            $camp = SchoolTrip::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Nyanga Outdoor Education Camp'],
                [
                    'destination' => 'Nyanga National Park',
                    'trip_date' => now()->addMonths(2)->toDateString(),
                    'return_date' => now()->addMonths(2)->addDays(2)->toDateString(),
                    'fee_amount' => 85.00,
                    'currency' => 'USD',
                    'capacity' => 30,
                    'is_active' => true,
                    'open_for_registration' => true,
                    'description' => 'Three-day outdoor education camp. Parents must complete consent forms.',
                ]
            );

            $past = SchoolTrip::updateOrCreate(
                ['school_id' => $school->id, 'name' => 'Science Centre Expo (Completed)'],
                [
                    'destination' => 'Harare Science Centre',
                    'trip_date' => now()->subMonths(1)->toDateString(),
                    'return_date' => now()->subMonths(1)->toDateString(),
                    'fee_amount' => 10.00,
                    'currency' => 'USD',
                    'capacity' => 35,
                    'is_active' => true,
                    'open_for_registration' => false,
                    'description' => 'Past trip kept for history in the operations hub.',
                ]
            );

            $enrollTargets = $linkedStudentIds->isNotEmpty()
                ? Student::query()->whereIn('id', $linkedStudentIds)->orderBy('id')->limit(2)->get()
                : $this->demoStudents($school, 2);

            foreach ($enrollTargets as $index => $student) {
                SchoolTripEnrollment::updateOrCreate(
                    [
                        'school_trip_id' => $museum->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'school_id' => $school->id,
                        'status' => 'enrolled',
                        'enrolled_at' => now()->subDays(2 + $index),
                    ]
                );
            }

            // Ensure camp stays open with capacity (no forced enrollments).
            unset($camp, $past);
        }
    }
}
