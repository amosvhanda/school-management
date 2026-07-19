<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTermsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_terms_are_publicly_readable(): void
    {
        $this->getJson('/api/v1/auth/platform-terms')
            ->assertOk()
            ->assertJsonPath('data.version', config('platform_terms.version'))
            ->assertJsonStructure(['data' => ['version', 'title', 'summary', 'content']]);
    }

    public function test_privacy_policy_is_publicly_readable(): void
    {
        $this->getJson('/api/v1/auth/privacy-policy')
            ->assertOk()
            ->assertJsonPath('data.version', config('platform_privacy.version'))
            ->assertJsonPath('data.title', config('platform_privacy.title'))
            ->assertJsonStructure(['data' => ['version', 'title', 'summary', 'content']]);
    }

    public function test_user_must_accept_current_platform_terms(): void
    {
        $user = User::factory()->withoutPlatformTerms()->create([
            'role' => 'admin',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $this->assertFalse($login->json('data.user.platform_terms_accepted'));

        $token = $login->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/auth/accept-platform-terms', [
                'accepted' => true,
                'version' => config('platform_terms.version'),
            ])
            ->assertOk()
            ->assertJsonPath('data.user.platform_terms_accepted', true);

        $this->assertTrue($user->fresh()->hasAcceptedCurrentPlatformTerms());
    }

    public function test_accepting_stale_terms_version_is_rejected(): void
    {
        $auth = $this->createAuthenticatedUser();
        $user = User::findOrFail($auth['user']->id);
        $user->forceFill([
            'platform_terms_version' => null,
            'platform_terms_accepted_at' => null,
        ])->save();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/auth/accept-platform-terms', [
                'accepted' => true,
                'version' => 'outdated.0',
            ])
            ->assertStatus(422);
    }
}
