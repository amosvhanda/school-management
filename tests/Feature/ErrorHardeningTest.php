<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class ErrorHardeningTest extends TestCase
{
    public function test_teacher_cannot_access_finance_payments(): void
    {
        $this->seed(RoleSeeder::class);
        $auth = $this->createAuthenticatedUser(role: 'teacher');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/payments')
            ->assertForbidden();
    }

    public function test_duplicate_teacher_assignment_is_rejected(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/teacher-assignments', [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'subject_id' => $subject->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['teacher_id']);
    }

    public function test_app_timezone_is_harare(): void
    {
        $this->assertSame('Africa/Harare', config('app.timezone'));
    }
}
