<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    public function test_teacher_cannot_list_or_update_users(): void
    {
        $admin = $this->createAuthenticatedUser(role: 'admin');
        $teacher = $this->createAuthenticatedUser(role: 'teacher', schoolId: $admin['school']->id);

        $this->withHeaders(['Authorization' => 'Bearer '.$teacher['token']])
            ->getJson('/api/v1/users')
            ->assertForbidden();

        $this->withHeaders(['Authorization' => 'Bearer '.$teacher['token']])
            ->putJson('/api/v1/users/'.$admin['user']->id, [
                'role' => 'admin',
                'permission_ids' => [7, 23],
            ])
            ->assertForbidden();
    }

    public function test_school_admin_cannot_assign_super_admin_role(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        $target = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'teacher',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->patchJson('/api/v1/users/'.$target->id.'/role', ['role' => 'super_admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_seeded_teacher_role_does_not_get_can_manage_teachers(): void
    {
        $this->seed(RoleSeeder::class);

        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $caps = app(PermissionService::class)->resolveCapabilities($auth['user']);

        $this->assertTrue($caps['isStaff']);
        $this->assertTrue($caps['canManageStudents']);
        $this->assertTrue($caps['canEnterExamResults']);
        $this->assertFalse($caps['canManageTeachers']);
    }

    public function test_seeded_finance_role_does_not_get_can_manage_teachers(): void
    {
        $this->seed(RoleSeeder::class);

        $auth = $this->createAuthenticatedUser(role: 'finance');
        $caps = app(PermissionService::class)->resolveCapabilities($auth['user']);

        $this->assertTrue($caps['canManageFinance']);
        $this->assertFalse($caps['canManageTeachers']);
    }

    public function test_invalid_permission_ids_rejected(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        $target = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'teacher',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson('/api/v1/users/'.$target->id, [
                'permission_ids' => [99999],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['permission_ids']);
    }
}
