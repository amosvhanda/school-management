<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDomain;
use App\Models\SchoolUserMembership;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantSwitchTest extends TestCase
{
    public function test_me_includes_school_memberships(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.school_id', $auth['school']->id)
            ->assertJsonFragment([
                'id' => $auth['school']->id,
                'is_current' => true,
            ]);

        $this->assertDatabaseHas('school_user_memberships', [
            'user_id' => $auth['user']->id,
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_user_can_switch_between_member_schools(): void
    {
        $auth = $this->createAuthenticatedUser();
        $secondSchool = School::factory()->create(['status' => 'active', 'name' => 'Second Academy']);

        SchoolUserMembership::create([
            'school_id' => $secondSchool->id,
            'user_id' => $auth['user']->id,
            'role' => $auth['user']->roleValue(),
            'is_default' => false,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/auth/switch-school', [
                'school_id' => $secondSchool->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.user.school_id', $secondSchool->id);

        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'id' => $auth['user']->id,
            'school_id' => $secondSchool->id,
        ]);
    }

    public function test_user_cannot_switch_to_unrelated_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create(['status' => 'active']);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/auth/switch-school', [
                'school_id' => $otherSchool->id,
            ])
            ->assertStatus(422);
    }

    public function test_login_on_member_domain_switches_active_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $user = $auth['user'];
        $user->update(['password' => Hash::make('Password123!')]);

        $secondSchool = School::factory()->create(['status' => 'active']);
        SchoolUserMembership::create([
            'school_id' => $secondSchool->id,
            'user_id' => $user->id,
            'role' => $user->roleValue(),
            'is_default' => false,
        ]);

        SchoolDomain::create([
            'school_id' => $secondSchool->id,
            'domain' => 'second.example.test',
            'is_primary' => true,
            'is_verified' => true,
            'status' => 'active',
            'verified_at' => now(),
            'verification_token' => 'token-second',
        ]);

        $this->withHeaders(['X-Forwarded-Host' => 'second.example.test'])
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'Password123!',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.school_id', $secondSchool->id);
    }
}
