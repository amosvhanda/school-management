<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWriteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_perform_core_write_actions(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
        ]);
        Sanctum::actingAs($admin);

        $class = ClassModel::factory()->create(['school_id' => $school->id]);
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/announcements', [
            'title' => 'Admin smoke notice',
            'message' => 'Posted during admin write smoke test.',
            'type' => 'info',
            'target_audience' => 'all',
            'date' => now()->toDateString(),
        ])->assertSuccessful();

        $this->postJson('/api/v1/teacher-portal/online-lessons', [
            'title' => 'Admin LMS session',
            'lesson_type' => 'live',
            'teacher_id' => $teacher->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertCreated();

        $this->postJson('/api/v1/teacher-portal/attendance/submit', [
            'class_id' => $class->id,
            'date' => now()->toDateString(),
        ])->assertOk();

        $this->postJson('/api/v1/teacher-portal/attendance/lock', [
            'class_id' => $class->id,
            'date' => now()->toDateString(),
        ])->assertOk();

        $this->assertDatabaseHas('announcements', [
            'school_id' => $school->id,
            'title' => 'Admin smoke notice',
        ]);

        $this->assertDatabaseHas('online_lessons', [
            'school_id' => $school->id,
            'title' => 'Admin LMS session',
            'teacher_id' => $teacher->id,
        ]);

        $this->postJson('/api/v1/fee-categories', [
            'name' => 'Admin Smoke Fee',
            'code' => 'ASF-'.uniqid(),
            'amount' => 50,
            'is_active' => true,
        ])->assertSuccessful();

        $this->postJson('/api/v1/leave-types', [
            'name' => 'Admin Smoke Leave',
            'days_allowed' => 5,
            'is_paid' => true,
            'is_active' => true,
        ])->assertSuccessful();

        $this->postJson('/api/v1/students', [
            'firstName' => 'Admin',
            'surname' => 'Smoke',
            'dateOfBirth' => '2012-06-01',
            'gender' => 'female',
            'class' => $class->name,
            'class_id' => $class->id,
        ])->assertCreated();
    }
}
