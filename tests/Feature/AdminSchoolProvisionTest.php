<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSchoolProvisionTest extends TestCase
{
    public function test_super_admin_can_register_school_with_admin_and_license(): void
    {
        config(['license.enforcement' => true]);

        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
            'password' => Hash::make('password'),
        ]);
        $token = $superAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/admin/schools', [
                'school_name' => 'Mufakose High School',
                'school_code' => 'MUFA-HS',
                'address' => 'Harare',
                'phone' => '+263771000111',
                'email' => 'office@mufakose.test',
                'currency' => 'USD',
                'admin_name' => 'Tendai Admin',
                'admin_email' => 'admin@mufakose.test',
                'admin_password' => 'Password123!',
                'admin_password_confirmation' => 'Password123!',
                'generate_and_activate_license' => true,
                'plan_type' => 'annual',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.school.code', 'MUFA-HS')
            ->assertJsonPath('data.admin.email', 'admin@mufakose.test')
            ->assertJsonPath('data.license_status', 'active');

        $this->assertNotEmpty($response->json('data.license_key'));

        $school = School::where('code', 'MUFA-HS')->first();
        $this->assertNotNull($school);
        $this->assertSame('active', $school->status);
        $this->assertSame('active', $school->license_status);

        $admin = User::where('email', 'admin@mufakose.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame($school->id, $admin->school_id);
        $this->assertTrue($admin->role === UserRole::Admin || $admin->roleValue() === 'admin');

        $this->assertDatabaseHas('schools', ['id' => $school->id, 'code' => 'MUFA-HS']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'school_id' => $school->id]);

        $adminToken = $admin->createToken('test')->plainTextToken;
        $this->withHeaders(['Authorization' => 'Bearer '.$adminToken])
            ->getJson('/api/v1/dashboard/kpis')
            ->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mufakose.test',
            'password' => 'Password123!',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'admin@mufakose.test');
    }

    public function test_non_super_admin_cannot_provision_school(): void
    {
        $school = School::factory()->create([
            'license_status' => 'active',
            'license_plan' => 'lifetime',
        ]);
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'school_id' => $school->id,
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/admin/schools', [
                'school_name' => 'Blocked School',
                'school_code' => 'BLOCKED',
                'admin_name' => 'No Access',
                'admin_email' => 'noaccess@blocked.test',
                'admin_password' => 'Password123!',
                'admin_password_confirmation' => 'Password123!',
            ])
            ->assertForbidden();
    }
}
