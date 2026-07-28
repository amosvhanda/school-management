<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolDomain;
use App\Models\Student;
use App\Services\Tenancy\TenantSessionService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantSessionTest extends TestCase
{
    public function test_tenant_session_service_partitions_cookie_and_domain(): void
    {
        Config::set('session.cookie', 'school-erp-session');
        $school = School::factory()->create(['status' => 'active']);

        app(TenantSessionService::class)->applyForRequest(
            \Illuminate\Http\Request::create('https://greenvalley.example.test/api/v1/settings/config', 'GET', [], [], [], [
                'HTTP_HOST' => 'greenvalley.example.test',
            ]),
            $school,
        );

        $this->assertSame("school-erp-session_s{$school->id}", config('session.cookie'));
        $this->assertSame('greenvalley.example.test', config('session.domain'));
    }

    public function test_resolved_domain_applies_tenant_session_cookie(): void
    {
        Config::set('session.cookie', 'school-erp-session');
        $school = School::factory()->create(['status' => 'active']);
        SchoolDomain::query()->create([
            'school_id' => $school->id,
            'domain' => 'tenant-session.example.test',
            'verification_token' => 'token123',
            'is_verified' => true,
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/settings/config', [
            'X-Forwarded-Host' => 'tenant-session.example.test',
        ])->assertOk();

        $this->assertSame("school-erp-session_s{$school->id}", config('session.cookie'));
        $this->assertSame('tenant-session.example.test', config('session.domain'));
    }
}
