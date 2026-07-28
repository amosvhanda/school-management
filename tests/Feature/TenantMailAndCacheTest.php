<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\SchoolSettingsService;
use App\Services\Tenancy\SchoolMailService;
use App\Services\Tenancy\TenantCache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantMailAndCacheTest extends TestCase
{
    public function test_school_mail_service_applies_branded_from_address(): void
    {
        $school = School::factory()->create([
            'name' => 'Green Valley High',
            'email' => 'office@greenvalley.test',
        ]);
        $settings = app(SchoolSettingsService::class);
        $settings->seedDefaults($school);
        $settings->set($school, 'mail', 'from_address', 'noreply@greenvalley.test');
        $settings->set($school, 'mail', 'from_name', 'Green Valley');

        $result = app(SchoolMailService::class)->applyForSchool($school);

        $this->assertSame('noreply@greenvalley.test', $result['from_address']);
        $this->assertSame('Green Valley', $result['from_name']);
        $this->assertSame('noreply@greenvalley.test', config('mail.from.address'));
        $this->assertSame('Green Valley', config('mail.from.name'));
    }

    public function test_school_mail_service_switches_to_tenant_smtp_when_enabled(): void
    {
        $school = School::factory()->create();
        $settings = app(SchoolSettingsService::class);
        $settings->seedDefaults($school);
        $settings->bulkSet($school, [
            ['group' => 'mail', 'key' => 'enabled', 'value' => true],
            ['group' => 'mail', 'key' => 'smtp_host', 'value' => 'smtp.school.test'],
            ['group' => 'mail', 'key' => 'smtp_port', 'value' => 465],
            ['group' => 'mail', 'key' => 'smtp_username', 'value' => 'mailer'],
            ['group' => 'mail', 'key' => 'smtp_password', 'value' => 'secret-pass'],
            ['group' => 'mail', 'key' => 'smtp_encryption', 'value' => 'ssl'],
            ['group' => 'mail', 'key' => 'from_address', 'value' => 'mailer@school.test'],
        ]);

        $result = app(SchoolMailService::class)->applyForSchool($school);

        $this->assertSame('school_smtp', $result['mailer']);
        $this->assertSame('school_smtp', Config::get('mail.default'));
        $this->assertSame('smtp.school.test', Config::get('mail.mailers.school_smtp.host'));
        $this->assertSame(465, Config::get('mail.mailers.school_smtp.port'));
        $this->assertSame('secret-pass', Config::get('mail.mailers.school_smtp.password'));
    }

    public function test_encrypted_mail_password_is_masked_in_settings_payload(): void
    {
        $auth = $this->createAuthenticatedUser();
        $settings = app(SchoolSettingsService::class);
        $settings->seedDefaults($auth['school']);
        $settings->set($auth['school'], 'mail', 'smtp_password', 'top-secret');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/settings/school')
            ->assertOk()
            ->assertJsonPath('data.mail.smtp_password', '********');

        $this->assertSame(
            'top-secret',
            $settings->get($auth['school'], 'mail', 'smtp_password')
        );
    }

    public function test_settings_cache_invalidates_on_update(): void
    {
        $school = School::factory()->create();
        $settings = app(SchoolSettingsService::class);
        $settings->seedDefaults($school);

        $first = $settings->getAll($school);
        $this->assertSame('Africa/Harare', $first['regional']['timezone']);

        TenantCache::for($school->id)->put('settings:all', [
            'regional' => ['timezone' => 'stale-value'],
        ], 300);

        $cached = $settings->getAll($school);
        $this->assertSame('stale-value', $cached['regional']['timezone']);

        $settings->set($school, 'regional', 'timezone', 'Africa/Johannesburg');
        $fresh = $settings->getAll($school);
        $this->assertSame('Africa/Johannesburg', $fresh['regional']['timezone']);
    }

    public function test_public_config_excludes_mail_settings(): void
    {
        $school = School::factory()->create();
        app(SchoolSettingsService::class)->seedDefaults($school);

        $this->withHeaders(['X-Forwarded-Host' => 'unused.example.test'])
            ->getJson('/api/v1/settings/config')
            ->assertOk();

        $public = app(SchoolSettingsService::class)->getAll($school, publicOnly: true);
        $this->assertArrayNotHasKey('smtp_password', $public['mail'] ?? []);
        $this->assertSame([], $public['mail'] ?? []);
    }
}
