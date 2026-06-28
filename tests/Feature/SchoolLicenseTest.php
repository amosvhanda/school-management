<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolLicenseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['license.enforcement' => true]);
    }

    public function test_super_admin_can_generate_license_key(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
            'password' => Hash::make('password'),
        ]);
        $token = $superAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/admin/licenses', [
                'plan_type' => 'monthly',
                'customer_name' => 'Harare High School',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['license_key', 'record' => ['id', 'plan_type', 'status']],
            ]);

        $this->assertStringStartsWith('SKERP-', $response->json('data.license_key'));
    }

    public function test_school_admin_can_activate_key_and_access_api(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        $generated = app(LicenseService::class)->generate($superAdmin, [
            'plan_type' => 'annual',
            'customer_name' => 'Test School',
        ]);

        $school = School::factory()->create([
            'license_status' => 'none',
            'license_plan' => null,
            'license_expires_at' => null,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'school_id' => $school->id,
            'password' => Hash::make('password'),
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/dashboard/kpis')
            ->assertStatus(402);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/license/activate', [
                'license_key' => $generated['license_key'],
            ])
            ->assertOk()
            ->assertJsonPath('data.license.status', 'active');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/dashboard/kpis')
            ->assertOk();
    }

    public function test_lifetime_key_never_expires(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        $generated = app(LicenseService::class)->generate($superAdmin, [
            'plan_type' => 'lifetime',
        ]);

        $school = School::factory()->create(['license_status' => 'none']);
        app(LicenseService::class)->activate($school, $generated['license_key']);

        $school->refresh();

        $this->assertSame('active', $school->license_status);
        $this->assertSame('lifetime', $school->license_plan);
        $this->assertNull($school->license_expires_at);
    }

    public function test_expired_license_blocks_api_but_allows_renewal_route(): void
    {
        $school = School::factory()->create([
            'license_status' => 'active',
            'license_plan' => 'monthly',
            'license_expires_at' => now()->subDays(30),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'school_id' => $school->id,
            'password' => Hash::make('password'),
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/students')
            ->assertStatus(402);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/license/status')
            ->assertOk()
            ->assertJsonPath('data.status', 'expired');
    }

    public function test_school_registration_requires_license_when_enforcement_enabled(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        $generated = app(LicenseService::class)->generate($superAdmin, [
            'plan_type' => 'quarterly',
        ]);

        $response = $this->postJson('/api/v1/schools/register', [
            'school_name' => 'Licensed School',
            'school_code' => 'LIC'.fake()->unique()->numberBetween(1000, 9999),
            'admin_name' => 'Licensed Admin',
            'admin_email' => 'licadmin'.fake()->unique()->numberBetween(1000, 9999).'@school.com',
            'admin_password' => 'Password123',
            'admin_password_confirmation' => 'Password123',
            'license_key' => $generated['license_key'],
        ]);

        $response->assertCreated();

        $school = School::find($response->json('school_id'));
        $this->assertSame('active', $school->license_status);
        $this->assertSame('quarterly', $school->license_plan);
    }
}
