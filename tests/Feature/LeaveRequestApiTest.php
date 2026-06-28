<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Tests\TestCase;

class LeaveRequestApiTest extends TestCase
{
    public function test_list_leave_requests_for_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        LeaveRequest::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'requested_by' => $auth['user']->id,
            'type' => 'annual',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek()->addDays(2),
            'days' => 3,
            'reason' => 'Family event',
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/leave-requests');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.teacher_name', $teacher->name);
    }

    public function test_approve_leave_request(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $leave = LeaveRequest::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'requested_by' => $auth['user']->id,
            'type' => 'sick',
            'start_date' => now(),
            'end_date' => now(),
            'days' => 1,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/leave-requests/{$leave->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_reject_leave_requires_notes(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $leave = LeaveRequest::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'requested_by' => $auth['user']->id,
            'type' => 'sick',
            'start_date' => now(),
            'end_date' => now(),
            'days' => 1,
            'status' => 'pending',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/leave-requests/{$leave->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/leave-requests/{$leave->id}/reject", [
            'notes' => 'Insufficient cover arranged',
        ])->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }
}
