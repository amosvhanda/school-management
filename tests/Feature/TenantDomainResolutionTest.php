<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\SchoolDomain;
use App\Models\User;
use App\Services\SchoolSettingsService;
use App\Services\TerminologyService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantDomainResolutionTest extends TestCase
{
    public function test_public_config_resolves_school_from_exact_domain_without_auth(): void
    {
        $school = School::factory()->create([
            'name' => 'Mufakose High',
            'code' => 'mufa',
            'status' => 'active',
        ]);

        SchoolDomain::create([
            'school_id' => $school->id,
            'domain' => 'mufa.example.test',
            'is_primary' => true,
            'is_verified' => true,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        app(SchoolSettingsService::class)->seedDefaults($school);
        app(TerminologyService::class)->seedDefaults($school);

        $this->withHeaders(['X-Forwarded-Host' => 'mufa.example.test'])
            ->getJson('/api/v1/settings/config')
            ->assertOk()
            ->assertJsonPath('data.school.id', $school->id)
            ->assertJsonPath('data.school.code', 'mufa')
            ->assertJsonStructure([
                'data' => ['settings', 'terminology', 'school' => ['id', 'name', 'code']],
            ]);
    }

    public function test_login_rejects_user_from_another_school_when_domain_resolves_a_school(): void
    {
        $schoolA = School::factory()->create([
            'name' => 'Alpha School',
            'code' => 'alpha',
            'status' => 'active',
        ]);
        $schoolB = School::factory()->create([
            'name' => 'Beta School',
            'code' => 'beta',
            'status' => 'active',
        ]);

        SchoolDomain::create([
            'school_id' => $schoolA->id,
            'domain' => 'alpha.example.test',
            'is_primary' => true,
            'is_verified' => true,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        User::factory()->create([
            'school_id' => $schoolB->id,
            'role' => UserRole::Admin,
            'email' => 'admin@beta.test',
            'password' => Hash::make('Password123!'),
        ]);

        $this->withHeaders(['X-Forwarded-Host' => 'alpha.example.test'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@beta.test',
                'password' => 'Password123!',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'This account does not belong to the school on this domain.');
    }

    public function test_login_allows_matching_school_on_domain(): void
    {
        $school = School::factory()->create([
            'name' => 'Gamma School',
            'code' => 'gamma',
            'status' => 'active',
        ]);

        SchoolDomain::create([
            'school_id' => $school->id,
            'domain' => 'gamma.example.test',
            'is_primary' => true,
            'is_verified' => true,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        User::factory()->create([
            'school_id' => $school->id,
            'role' => UserRole::Admin,
            'email' => 'admin@gamma.test',
            'password' => Hash::make('Password123!'),
        ]);

        $this->withHeaders(['X-Forwarded-Host' => 'gamma.example.test'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@gamma.test',
                'password' => 'Password123!',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.school_id', $school->id);
    }
}
