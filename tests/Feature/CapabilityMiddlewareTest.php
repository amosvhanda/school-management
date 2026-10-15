<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class CapabilityMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_capability_middleware_blocks_parent_from_staff_upload(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'parent');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->post('/api/v1/uploads', [])
            ->assertForbidden();
    }

    public function test_security_headers_present_on_api_responses(): void
    {
        $this->getJson('/api/v1/settings/config')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
