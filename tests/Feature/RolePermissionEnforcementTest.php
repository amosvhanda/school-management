<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Tests\TestCase;

class RolePermissionEnforcementTest extends TestCase
{
    public function test_login_returns_permissions_and_capabilities_from_role(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'finance');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $auth['user']->email,
            'password' => 'password',
        ])->assertOk();

        $response->assertJsonPath('data.user.capabilities.canManageFinance', true);
        $this->assertContains('finance.manage', $response->json('data.user.permissions'));
    }

    public function test_updating_role_permissions_changes_resolved_capabilities(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacherRole = Role::updateOrCreate(
            ['slug' => 'teacher'],
            [
                'name' => 'Teacher',
                'description' => 'Teaching staff',
                'permission_ids' => [10],
            ]
        );

        $teacher = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'teacher',
            'email' => 'limited-teacher@test.co.zw',
        ]);

        $service = app(PermissionService::class);
        $caps = $service->resolveCapabilities($teacher);

        $this->assertTrue($caps['isStaff']);
        $this->assertFalse($caps['canManageStudents']);
        $this->assertFalse($caps['canManageExaminations']);
    }

    public function test_teacher_without_roles_manage_permission_cannot_list_roles(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/roles')
            ->assertForbidden();
    }

    public function test_permissions_rules_endpoint_returns_capability_mapping(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/permissions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'slug', 'capabilities'],
                ],
            ]);
    }

    public function test_user_permission_override_grants_library_without_admin_role(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $libraryPermissionId = collect(config('permissions'))
            ->firstWhere('slug', 'library.manage')['id'] ?? null;

        $this->assertNotNull($libraryPermissionId);

        $auth['user']->update(['permission_ids' => [$libraryPermissionId]]);

        $service = app(PermissionService::class);
        $this->assertTrue($service->hasCapability($auth['user']->fresh(), 'canManageLibrary'));
        $this->assertTrue($service->hasPermission($auth['user']->fresh(), 'library.manage'));

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/library/books')
            ->assertOk();
    }

    public function test_teacher_without_library_permission_cannot_access_library(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/library/books')
            ->assertForbidden();
    }

    public function test_teacher_without_operations_permission_cannot_access_hostels_or_procurement(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)->getJson('/api/v1/hostels')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/procurement/requisitions')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/assets')->assertForbidden();
    }

    public function test_teacher_without_compliance_permission_cannot_manage_consent_forms(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/consent-forms')
            ->assertForbidden();
    }
}
