<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\PermissionService;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class TeacherPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_seeded_teacher_slugs_match_config_matrix(): void
    {
        $expected = config('teacher_permissions.slugs');
        $role = Role::query()->where('slug', 'teacher')->firstOrFail();
        $service = app(PermissionService::class);

        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $slugs = $service->permissionSlugsForUser($auth['user']);

        foreach ($expected as $slug) {
            $this->assertContains($slug, $slugs, "Teacher missing expected slug: {$slug}");
        }

        foreach (config('teacher_permissions.slugs_denied') as $denied) {
            $this->assertNotContains($denied, $slugs, "Teacher must not have slug: {$denied}");
        }

        $catalogIds = collect(config('permissions'))
            ->whereIn('slug', $expected)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing($catalogIds, $role->permission_ids ?? []);
    }

    public function test_seeded_teacher_capabilities_match_matrix(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $caps = app(PermissionService::class)->resolveCapabilities($auth['user']);

        foreach (config('teacher_permissions.capabilities_granted') as $cap) {
            $this->assertTrue($caps[$cap] ?? false, "Expected capability granted: {$cap}");
        }

        foreach (config('teacher_permissions.capabilities_denied') as $cap) {
            $this->assertFalse($caps[$cap] ?? true, "Expected capability denied: {$cap}");
        }
    }

    public function test_teacher_is_denied_admin_finance_and_audit_endpoints(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)->getJson('/api/v1/users')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/roles')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/invoices')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/library/books')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/audit-logs')->assertForbidden();
    }

    public function test_teacher_cannot_access_unassigned_class_in_portal(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
        ]);
        $ownClass = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $otherClass = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        TeacherAssignment::create([
            'school_id' => $auth['school']->id,
            'teacher_id' => $teacher->id,
            'class_id' => $ownClass->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);

        $foreignStudent = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'class_id' => $otherClass->id,
        ]);

        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)
            ->postJson('/api/v1/teacher-portal/attendance/submit', [
                'class_id' => $otherClass->id,
                'date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->withHeaders($headers)
            ->postJson('/api/v1/teacher-portal/behaviour', [
                'student_id' => $foreignStudent->id,
                'points' => 1,
                'category' => 'positive',
                'description' => 'Good work',
            ])
            ->assertForbidden();
    }

    public function test_teacher_can_access_assigned_class_attendance_submit(): void
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

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/teacher-portal/attendance/submit', [
                'class_id' => $class->id,
                'date' => now()->toDateString(),
            ])
            ->assertOk();
    }

    public function test_permission_service_defaults_match_config_when_role_has_no_db_perms(): void
    {
        Role::query()->where('slug', 'teacher')->update(['permission_ids' => []]);

        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $slugs = app(PermissionService::class)->permissionSlugsForUser($auth['user']);

        $this->assertEqualsCanonicalizing(config('teacher_permissions.slugs'), $slugs);
    }
}
