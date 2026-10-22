<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class PreskoolUiDemoSeederTest extends TestCase
{
    public function test_preskool_ui_demo_seeder_populates_showcase_school(): void
    {
        $this->seed(DatabaseSeeder::class);

        $school = School::query()->orderBy('id')->first();
        $this->assertNotNull($school);
        $this->assertNotNull($school->principal_name);

        $this->assertTrue(
            Student::query()
                ->where('school_id', $school->id)
                ->whereNotNull('photo_url')
                ->exists(),
        );

        $this->assertGreaterThanOrEqual(
            5,
            Employee::query()->where('school_id', $school->id)->count(),
        );

        $this->assertGreaterThanOrEqual(
            3,
            StudentDocument::query()->where('school_id', $school->id)->count(),
        );

        $this->assertGreaterThanOrEqual(
            1,
            School::query()->where('parent_school_id', $school->id)->count(),
        );

        $this->assertNotNull(
            User::query()->where('email', 'admin@school.co.zw')->value('avatar_url'),
        );
    }
}
