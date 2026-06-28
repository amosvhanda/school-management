<?php

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('login');
        parent::tearDown();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $school = School::factory()->create();
        User::factory()->create([
            'email' => 'secure@example.com',
            'password' => Hash::make('Password123'),
            'school_id' => $school->id,
            'role' => 'admin',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'secure@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'secure@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_weak_password_rejected_on_school_registration(): void
    {
        $response = $this->postJson('/api/v1/schools/register', [
            'school_name' => 'Weak Password School',
            'school_code' => 'WEAK001',
            'admin_name' => 'Admin',
            'admin_email' => 'weak@school.com',
            'admin_password' => 'short',
            'admin_password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admin_password']);
    }

    public function test_change_password_revokes_existing_tokens(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/user/change-password', [
            'old_password' => 'password',
            'new_password' => 'Newpassword1',
            'new_password_confirmation' => 'Newpassword1',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
