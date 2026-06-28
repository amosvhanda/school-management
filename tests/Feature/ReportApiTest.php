<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Student;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    public function test_export_academic_report_as_json(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        Grade::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/reports/export?type=academic-performance&format=json');

        $response->assertOk()
            ->assertJsonPath('data.report_type', 'academic-performance');
    }

    public function test_export_attendance_report_as_csv(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->get('/api/v1/reports/export?type=attendance&format=csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_store_report_template(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/reports', [
            'name' => 'Weekly Attendance',
            'category' => 'attendance',
            'frequency' => 'weekly',
            'recipients' => ['admin@school.test'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Weekly Attendance');
    }

    public function test_class_report(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/reports/class/1');

        $response->assertOk()
            ->assertJsonPath('data.report_type', 'class');
    }
}
