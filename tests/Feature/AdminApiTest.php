<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    public function test_fee_categories_crud(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/fee-categories', [
            'name' => 'Tuition',
            'description' => 'Term tuition fees',
            'order' => 1,
        ]);

        $create->assertCreated();
        $id = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/fee-categories')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/fee-categories/{$id}", ['description' => 'Updated'])
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/fee-categories/{$id}")
            ->assertOk();
    }

    public function test_grading_scales_crud(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/grading-scales', [
            'grade' => 'A',
            'min_score' => 80,
            'max_score' => 100,
            'description' => 'Excellent',
            'order' => 1,
        ]);

        $create->assertCreated();
        $id = $create->json('id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/grading-scales')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/grading-scales/get-grade', ['score' => 85])
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/grading-scales/{$id}")
            ->assertOk();
    }

    public function test_users_crud(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/users', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@school.test',
            'role' => 'teacher',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $create->assertCreated();
        $id = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/users')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/users/{$id}")
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->patchJson("/api/v1/users/{$id}/role", ['role' => 'teacher'])
            ->assertOk();
    }

    public function test_roles_and_permissions(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/roles')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/permissions')
            ->assertOk();
    }

    public function test_dashboard_endpoints(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/dashboard/activity')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/dashboard/monthly-stats')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/dashboard/recent-activity')
            ->assertOk();
    }

    public function test_payroll_summary_endpoints(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/payroll/summary')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/payroll/trends')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/payroll/department-summary')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/payroll/teachers')
            ->assertOk();
    }

    public function test_enrollment_approve_and_reject(): void
    {
        $auth = $this->createAuthenticatedUser();
        $grade = \App\Models\GradeLevel::factory()->create([
            'school_id' => $auth['school']->id,
            'name' => 'Form 1',
            'order' => 1,
        ]);
        $class = \App\Models\ClassModel::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $grade->id,
            'name' => 'Form 1A',
            'capacity' => 40,
        ]);

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/enrollment-applications', [
            'first_name' => 'Tariro',
            'surname' => 'Moyo',
            'date_of_birth' => '2012-05-01',
            'gender' => 'female',
            'phone' => '+263771234567',
            'address' => '123 Main St',
            'grade_applying_for' => 'Form 1',
            'academic_year' => (string) now()->year,
            'guardian_first_name' => 'John',
            'guardian_surname' => 'Moyo',
            'guardian_phone' => '+263771234568',
            'guardian_relationship' => 'parent',
            'guardian_address' => '456 Guardian Street',
            'emergency_contact' => 'Emergency Contact',
            'emergency_phone' => '+263771234569',
        ]);

        $create->assertCreated();
        $id = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/enrollment-applications/{$id}")
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/enrollment-applications/{$id}/approve", [
                'class_id' => $class->id,
            ])
            ->assertOk();

        $rejectId = DB::table('enrollment_applications')->insertGetId([
            'school_id' => $auth['school']->id,
            'first_name' => 'Reject',
            'surname' => 'Case',
            'date_of_birth' => '2011-01-01',
            'gender' => 'male',
            'phone' => '+263771234569',
            'address' => '1 Test Rd',
            'grade_applying_for' => 'Form 2',
            'academic_year' => (string) now()->year,
            'guardian_first_name' => 'G',
            'guardian_surname' => 'One',
            'guardian_phone' => '+263771234570',
            'guardian_relationship' => 'parent',
            'guardian_address' => '2 Test Rd',
            'emergency_contact' => 'EC',
            'emergency_phone' => '+263771234571',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/enrollment-applications/{$rejectId}/reject", ['notes' => 'Incomplete docs'])
            ->assertOk();
    }

    public function test_announcement_update_and_delete(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/announcements', [
            'title' => 'Sports Day',
            'message' => 'Friday sports day',
            'type' => 'info',
            'target_audience' => 'all',
            'date' => now()->format('Y-m-d'),
        ]);

        $create->assertCreated();
        $id = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/announcements/{$id}", ['title' => 'Sports Day Updated'])
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/announcements/{$id}")
            ->assertOk();
    }

    public function test_leave_request_reject(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = \App\Models\Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $leave = \App\Models\LeaveRequest::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'requested_by' => $auth['user']->id,
            'type' => 'annual',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek()->addDays(2),
            'days' => 3,
            'status' => 'pending',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/leave-requests/{$leave->id}/reject", ['reason' => 'Peak exam period'])
            ->assertOk();
    }

    public function test_school_profile_endpoints(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/school')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/user/profile')
            ->assertOk();
    }
}
