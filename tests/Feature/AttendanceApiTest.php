<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel;

class AttendanceApiTest extends TestCase
{
    public function test_get_attendance_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/attendance');

        $response->assertStatus(200);
    }

    public function test_create_attendance_skips_inactive_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $active = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        $inactive = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'status' => 'inactive',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/attendance', [
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'records' => [
                ['student_id' => $active->id, 'status' => 'present'],
                ['student_id' => $inactive->id, 'status' => 'present'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.0.student_id', $active->id)
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseHas('attendance', [
            'student_id' => $active->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseMissing('attendance', [
            'student_id' => $inactive->id,
        ]);
    }

    public function test_create_attendance_rejects_all_inactive_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $inactive = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
            'status' => 'inactive',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/attendance', [
            'class_id' => $class->id,
            'date' => now()->format('Y-m-d'),
            'records' => [
                ['student_id' => $inactive->id, 'status' => 'present'],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('error_code', 'student_inactive');
    }

    public function test_resaving_attendance_updates_existing_row(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);
        $date = now()->format('Y-m-d');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        Attendance::withoutGlobalScopes()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => $date,
            'status' => 'present',
            'marked_by' => $auth['user']->id,
        ]);

        $this->withHeaders($headers)->postJson('/api/v1/attendance', [
            'class_id' => $class->id,
            'date' => $date,
            'records' => [
                [
                    'student_id' => $student->id,
                    'status' => 'absent',
                    'remarks' => 'Updated mark',
                ],
            ],
        ])->assertStatus(201);

        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'absent',
            'remarks' => 'Updated mark',
        ]);

        $this->assertSame(
            1,
            Attendance::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->whereDate('date', $date)
                ->where('class_id', $class->id)
                ->count(),
        );
    }

    public function test_resaving_attendance_merges_legacy_null_class_row(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);
        $date = now()->format('Y-m-d');

        Attendance::withoutGlobalScopes()->create([
            'school_id' => null,
            'student_id' => $student->id,
            'class_id' => null,
            'date' => $date,
            'status' => 'present',
            'marked_by' => $auth['user']->id,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/attendance', [
            'class_id' => $class->id,
            'date' => $date,
            'records' => [
                [
                    'student_id' => $student->id,
                    'status' => 'late',
                ],
            ],
        ])->assertStatus(201);

        $this->assertSame(
            1,
            Attendance::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->whereDate('date', $date)
                ->count(),
        );

        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'late',
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_locked_attendance_rejects_overwrite_without_force(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);
        $date = now()->format('Y-m-d');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        Attendance::withoutGlobalScopes()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => $date,
            'status' => 'present',
            'marked_by' => $auth['user']->id,
            'locked_at' => now(),
            'locked_by' => $auth['user']->id,
        ]);

        $this->withHeaders($headers)->postJson('/api/v1/attendance', [
            'class_id' => $class->id,
            'date' => $date,
            'overwrite' => true,
            'records' => [
                [
                    'student_id' => $student->id,
                    'status' => 'absent',
                ],
            ],
        ])->assertStatus(423)
            ->assertJsonPath('error_code', 'attendance_locked');

        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'status' => 'present',
        ]);
    }

    public function test_admin_can_force_update_locked_attendance(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $class->id,
        ]);
        $date = now()->format('Y-m-d');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        Attendance::withoutGlobalScopes()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => $date,
            'status' => 'present',
            'marked_by' => $auth['user']->id,
            'locked_at' => now(),
            'locked_by' => $auth['user']->id,
        ]);

        $this->withHeaders($headers)->postJson('/api/v1/attendance', [
            'class_id' => $class->id,
            'date' => $date,
            'overwrite' => true,
            'force' => true,
            'records' => [
                [
                    'student_id' => $student->id,
                    'status' => 'absent',
                    'remarks' => 'Admin correction',
                ],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->id,
            'status' => 'absent',
            'remarks' => 'Admin correction',
        ]);
    }

    public function test_get_today_attendance_summary(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/attendance/today/summary');

        $response->assertStatus(200);
    }

    public function test_get_student_attendance_summary(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/attendance/student/{$student->id}/summary");

        $response->assertStatus(200);
    }

    public function test_get_class_attendance_report(): void
    {
        $auth = $this->createAuthenticatedUser();
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/attendance/class/{$class->id}/report");

        $response->assertStatus(200);
    }
}
