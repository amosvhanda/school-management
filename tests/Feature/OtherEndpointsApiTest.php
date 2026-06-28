<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Subject;
use App\Models\Payroll;
use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\Assignment;

class OtherEndpointsApiTest extends TestCase
{
    public function test_get_subjects(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/subjects');

        $response->assertStatus(200);
    }

    public function test_get_leave_requests(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/leave-requests');

        $response->assertStatus(200);
    }

    public function test_get_payroll_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/payroll');

        $response->assertStatus(200);
    }

    public function test_generate_payroll(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = \App\Models\Teacher::factory()->create(['school_id' => $auth['school']->id]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/payroll/generate', [
            'month' => now()->month,
            'year' => now()->year,
            'teacher_ids' => [$teacher->id],
        ]);

        $response->assertStatus(201);
    }

    public function test_get_parent_children(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = \App\Models\User::factory()->create([
            'role' => 'parent',
            'school_id' => $auth['school']->id,
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/parents/{$parent->id}/children");

        // This might return 200 with empty array or 404 if parent has no children
        $response->assertStatus(200);
    }

    public function test_get_reports(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/reports/academic-performance');

        $response->assertStatus(200);
    }

    public function test_get_attendance_report(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/reports/attendance');

        $response->assertStatus(200);
    }

    public function test_get_financial_report(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/reports/financial');

        $response->assertStatus(200);
    }

    public function test_get_settings(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(\App\Services\SchoolSettingsService::class)->seedDefaults($auth['school']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/settings/school');

        $response->assertStatus(200);
    }

    public function test_update_settings(): void
    {
        $auth = $this->createAuthenticatedUser();
        app(\App\Services\SchoolSettingsService::class)->seedDefaults($auth['school']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson('/api/v1/settings/school', [
            'settings' => [
                [
                    'group' => 'regional',
                    'key' => 'currency',
                    'value' => 'ZWG',
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_get_enrollment_applications(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/enrollment-applications');

        $response->assertStatus(200);
    }

    public function test_create_enrollment_application(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/enrollment-applications', [
            'first_name' => 'New',
            'surname' => 'Student',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'phone' => '+263771234567',
            'address' => '123 Test Street',
            'grade_applying_for' => 'Form 1',
            'academic_year' => (string) now()->year,
            'guardian_first_name' => 'Guardian',
            'guardian_surname' => 'Name',
            'guardian_phone' => '+263771234568',
            'guardian_relationship' => 'parent',
            'guardian_address' => '456 Guardian Street',
            'emergency_contact' => 'Emergency Contact',
            'emergency_phone' => '+263771234569',
        ]);

        $response->assertStatus(201);
    }

    public function test_get_announcements(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/announcements');

        $response->assertStatus(200);
    }

    public function test_create_announcement(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/announcements', [
            'title' => 'Test Announcement',
            'message' => 'This is a test announcement',
            'type' => 'info',
            'target_audience' => 'all',
            'date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
    }

    public function test_get_finance_summary(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/finance/summary');

        $response->assertStatus(200);
    }

    public function test_get_dashboard_kpis(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/dashboard/kpis');

        $response->assertStatus(200);
    }

    public function test_get_assignments(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/assignments');

        $response->assertStatus(200);
    }

    public function test_create_assignment(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = \App\Models\ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = \App\Models\Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/assignments', [
            'title' => 'Test Assignment',
            'subject' => 'Mathematics',
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'description' => 'Assignment description',
        ]);

        $response->assertStatus(201);
    }
}
