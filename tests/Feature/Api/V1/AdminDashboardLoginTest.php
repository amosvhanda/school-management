<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_access_dashboard_kpis(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $auth['user']->email,
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $token = $login->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', $auth['user']->email);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/dashboard/kpis')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/settings/config')
            ->assertOk();
    }

    public function test_seeded_admin_credentials_login(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@school.co.zw',
            'password' => 'admin123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonPath('data.user.email', 'admin@school.co.zw');
    }

    public function test_seeded_super_admin_credentials_login(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'super@school.co.zw',
            'password' => 'super123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'super_admin')
            ->assertJsonPath('data.user.school_id', null)
            ->assertJsonPath('data.user.capabilities.isSuperAdmin', true);

        $token = $response->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/settings/config')
            ->assertOk()
            ->assertJsonPath('data.settings', []);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/licenses')
            ->assertOk()
            ->assertJsonStructure(['data' => ['keys', 'summary']]);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/schools')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'schools',
                    'summary' => ['schools', 'keys'],
                ],
            ]);
    }
}
