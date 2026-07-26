<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

class AuthApiTest extends TestCase
{
    public function test_school_registration(): void
    {
        $response = $this->postJson('/api/v1/schools/register', [
            'school_name' => 'Test School',
            'school_code' => 'TEST' . fake()->unique()->numberBetween(1000, 9999),
            'admin_name' => 'Admin User',
            'admin_email' => 'admin' . fake()->unique()->numberBetween(1000, 9999) . '@school.com',
            'admin_password' => 'Password123',
            'admin_password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'school_id',
                'data',
            ]);
    }

    public function test_login_with_valid_credentials(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'school_id' => $school->id,
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_with_authentication(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_get_current_user(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'name', 'email', 'role']],
            ]);
    }

    public function test_get_user_profile(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/user/profile');

        $response->assertStatus(200);
    }

    public function test_update_user_profile(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson('/api/v1/user/profile', [
            'name' => 'Updated Name',
            'phone' => '+263771234567',
        ]);

        $response->assertStatus(200);
    }

    public function test_change_password(): void
    {
        $auth = $this->createAuthenticatedUser();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/user/change-password', [
            'old_password' => 'password',
            'new_password' => 'Newpassword1',
            'new_password_confirmation' => 'Newpassword1',
        ]);

        $response->assertStatus(200);
    }

    public function test_get_users_list(): void
    {
        $auth = $this->createAuthenticatedUser('admin');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/users');

        $response->assertStatus(200);
    }

    public function test_forgot_password_accepts_known_email(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'email' => 'resetme@example.com',
            'password' => Hash::make('password'),
            'school_id' => $school->id,
            'role' => 'admin',
        ]);

        \Illuminate\Support\Facades\Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();
    }

    public function test_forgot_password_validation_requires_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [])
            ->assertStatus(422);
    }

    public function test_reset_password_with_valid_token(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'email' => 'resetok@example.com',
            'password' => Hash::make('Oldpassword1'),
            'school_id' => $school->id,
            'role' => 'admin',
        ]);

        $token = \Illuminate\Support\Facades\Password::broker(config('fortify.passwords'))
            ->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'Newpassword1',
            'password_confirmation' => 'Newpassword1',
        ])->assertOk();

        $this->assertTrue(Hash::check('Newpassword1', $user->fresh()->password));
    }
}
