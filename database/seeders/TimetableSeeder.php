<?php

namespace Database\Seeders;

use App\Models\School;
use App\Services\TimetableGeneratorService;
use Illuminate\Database\Seeder;

/**
 * Build weekly timetables from teacher assignments (subject teachers per class).
 */
class TimetableSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(TimetableGeneratorService::class);

        foreach (School::query()->cursor() as $school) {
            $generator->generateBulk(
                schoolId: $school->id,
                classIds: null,
                dayStart: '07:30',
                periodMinutes: 45,
                replaceExisting: true,
            );
        }
    }
}
