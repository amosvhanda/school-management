<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class TwoFactorEnforcementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_without_2fa_is_blocked_when_enforcement_enabled(): void
    {
        config(['security.require_two_factor' => true]);

        $auth = $this->createAuthenticatedUser(role: 'admin');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/students')
            ->assertForbidden()
            ->assertJsonPath('code', 'two_factor_setup_required');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/auth/two-factor')
            ->assertOk();
    }

    public function test_go_live_check_command_runs(): void
    {
        $this->artisan('saas:go-live-check')->assertSuccessful();
    }
}
