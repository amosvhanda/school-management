<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\SchoolBackup;
use App\Models\SchoolDomain;
use App\Models\Student;
use App\Models\User;
use App\Services\Tenancy\TenantCache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantBackupAndLifecycleTest extends TestCase
{
    public function test_super_admin_can_create_and_restore_school_backup(): void
    {
        $school = School::factory()->create(['status' => 'active']);
        Student::factory()->create(['school_id' => $school->id]);
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        Sanctum::actingAs($superAdmin);

        $create = $this->postJson("/api/v1/admin/schools/{$school->id}/backups")
            ->assertCreated()
            ->assertJsonPath('data.backup.school_id', $school->id);

        $backupId = $create->json('data.backup.id');
        $this->assertNotNull($backupId);

        Student::query()->where('school_id', $school->id)->delete();
        $this->assertSame(0, Student::query()->where('school_id', $school->id)->count());

        $this->postJson("/api/v1/admin/schools/{$school->id}/backups/{$backupId}/restore")
            ->assertOk()
            ->assertJsonPath('data.backup.restored_at', fn ($value) => $value !== null);

        $this->assertSame(1, Student::query()->where('school_id', $school->id)->count());
        $this->assertDatabaseHas('school_backups', [
            'id' => $backupId,
            'school_id' => $school->id,
            'status' => 'completed',
        ]);
    }

    public function test_super_admin_can_deprovision_school_and_block_access(): void
    {
        $school = School::factory()->create([
            'status' => 'active',
            'license_status' => 'active',
            'license_plan' => 'lifetime',
        ]);
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'school_id' => $school->id,
        ]);
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        Sanctum::actingAs($superAdmin);
        $this->deleteJson("/api/v1/admin/schools/{$school->id}")
            ->assertOk()
            ->assertJsonPath('data.school.status', 'deleted')
            ->assertJsonPath('data.backup.school_id', $school->id);

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/dashboard/kpis')
            ->assertForbidden()
            ->assertJsonPath('code', 'school_deleted');
    }

    public function test_super_admin_can_fetch_school_usage_snapshot(): void
    {
        $school = School::factory()->create();
        User::factory()->count(2)->create(['school_id' => $school->id, 'role' => UserRole::Teacher]);
        Student::factory()->count(3)->create(['school_id' => $school->id]);
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        Sanctum::actingAs($superAdmin);
        $this->getJson("/api/v1/admin/schools/{$school->id}/usage")
            ->assertOk()
            ->assertJsonPath('data.usage.students', 3)
            ->assertJsonPath('data.usage.users', 2);
    }

    public function test_tenant_cache_partitions_keys_by_school(): void
    {
        $schoolA = TenantCache::for(1);
        $schoolB = TenantCache::for(2);

        $schoolA->put('settings', ['name' => 'Alpha'], 60);
        $schoolB->put('settings', ['name' => 'Beta'], 60);

        $this->assertSame(['name' => 'Alpha'], $schoolA->get('settings'));
        $this->assertSame(['name' => 'Beta'], $schoolB->get('settings'));
        $this->assertNotSame($schoolA->key('settings'), $schoolB->key('settings'));
    }

    public function test_domain_manual_verification_requires_force_flag(): void
    {
        $school = School::factory()->create();
        $domain = SchoolDomain::query()->create([
            'school_id' => $school->id,
            'domain' => 'manual.example.test',
            'verification_token' => 'abc123token',
            'is_verified' => false,
            'status' => 'active',
        ]);
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/v1/admin/schools/{$school->id}/domains/{$domain->id}/verify")
            ->assertStatus(422)
            ->assertJsonPath('code', 'domain_verification_failed');

        $this->postJson("/api/v1/admin/schools/{$school->id}/domains/{$domain->id}/verify?force=1")
            ->assertOk()
            ->assertJsonPath('data.domain.is_verified', true);
    }
}
