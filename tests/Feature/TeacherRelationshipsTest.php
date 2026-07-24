<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\TeacherResolutionService;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class TeacherRelationshipsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_resolution_links_teacher_by_school_scoped_email_and_persists_user_id(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
        ]);

        $resolved = app(TeacherResolutionService::class)->resolveForUser($auth['user']);

        $this->assertNotNull($resolved);
        $this->assertSame($teacher->id, $resolved->id);
        $this->assertSame($auth['user']->id, $resolved->fresh()->user_id);
    }

    public function test_auth_me_includes_teacher_id_after_email_link(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $teacherId = $response->json('data.teacher_id')
            ?? $response->json('data.user.teacher_id')
            ?? $response->json('teacher_id');

        $this->assertNotNull($teacherId, 'Expected teacher_id in auth/me payload');
    }

    public function test_my_classes_excludes_assignments_without_class_id(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
        ]);
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);
        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => null,
            'subject_id' => null,
            'grade_level_id' => null,
            'role' => 'form_teacher',
            'is_active' => true,
        ]);

        $payload = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/teacher-portal/classes')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $payload);
        $this->assertSame($class->id, $payload[0]['class_id']);
        $this->assertNotSame(0, $payload[0]['class_id']);
    }

    public function test_teacher_assignment_store_rejects_empty_relationship(): void
    {
        $admin = $this->createAuthenticatedUser(role: 'admin');
        $teacher = Teacher::factory()->create(['school_id' => $admin['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$admin['token']])
            ->postJson('/api/v1/teacher-assignments', [
                'teacher_id' => $teacher->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['class_id']);
    }

    public function test_teacher_assignment_store_rejects_cross_school_subject(): void
    {
        $admin = $this->createAuthenticatedUser(role: 'admin');
        $other = $this->createAuthenticatedUser(role: 'admin');
        $teacher = Teacher::factory()->create(['school_id' => $admin['school']->id]);
        $class = ClassModel::factory()->create(['school_id' => $admin['school']->id]);
        $foreignSubject = Subject::factory()->create(['school_id' => $other['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$admin['token']])
            ->postJson('/api/v1/teacher-assignments', [
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'subject_id' => $foreignSubject->id,
            ])
            ->assertNotFound();
    }

    public function test_teacher_can_list_own_assignments_via_resolution(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => null,
            'email' => $auth['user']->email,
        ]);
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);

        $rows = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/teacher-assignments')
            ->assertOk()
            ->json();

        $list = $rows['data'] ?? $rows;
        $this->assertNotEmpty($list);
        $this->assertSame($teacher->id, $list[0]['teacher_id']);
    }
}
