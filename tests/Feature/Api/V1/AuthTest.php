<?php

namespace Tests\Feature\Api\V1;

use App\Models\CustomField;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $auth['user']->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_with_invalid_credentials_returns_422(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $auth['user']->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.user.email', $auth['user']->email);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
