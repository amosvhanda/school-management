<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

class TwoFactorAuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_login_requires_two_factor_challenge_when_confirmed(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        /** @var User $user */
        $user = $auth['user'];

        app(EnableTwoFactorAuthentication::class)($user, true);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.two_factor_required', true)
            ->assertJsonStructure(['data' => ['challenge_token']])
            ->assertJsonMissingPath('data.token');
    }

    public function test_two_factor_challenge_issues_token_with_valid_code(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        /** @var User $user */
        $user = $auth['user'];

        app(EnableTwoFactorAuthentication::class)($user, true);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $challenge = $login->json('data.challenge_token');
        $code = $user->recoveryCodes()[0];

        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']])
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_authenticated_user_can_enable_two_factor(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/auth/two-factor')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonStructure(['data' => ['qr_code_svg', 'setup_key', 'recovery_codes']]);
    }
}
