<?php

namespace Tests\Feature;

use App\Services\Messaging\WhatsAppService;
use App\Services\Tenancy\TenantStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagingAndStorageTest extends TestCase
{
    public function test_whatsapp_service_logs_when_disabled(): void
    {
        config(['services.whatsapp.enabled' => false]);
        Log::shouldReceive('info')->once();

        $sent = app(WhatsAppService::class)->send('+263771234567', 'Hello from School ERP');

        $this->assertFalse($sent);
    }

    public function test_sms_service_logs_when_disabled(): void
    {
        config(['services.sms.enabled' => false]);
        Log::shouldReceive('info')->once();

        $sent = app(\App\Services\Messaging\SmsService::class)->send('+263771234567', 'SMS hello');

        $this->assertFalse($sent);
    }

    public function test_whatsapp_twilio_uses_content_template_when_configured(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.provider' => 'twilio',
            'services.whatsapp.twilio_sid' => 'ACtest123',
            'services.whatsapp.twilio_token' => 'secret',
            'services.whatsapp.twilio_from' => 'whatsapp:+14155238886',
            'services.whatsapp.twilio_content_sid' => 'HXtemplate123',
            'services.whatsapp.twilio_content_variables' => '{"1":"12/1","2":"3pm"}',
            'services.whatsapp.use_template' => true,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['sid' => 'SM123'], 201),
        ]);

        $sent = app(WhatsAppService::class)->send('+263710939310', 'ignored when template vars set');

        $this->assertTrue($sent);
        \Illuminate\Support\Facades\Http::assertSentCount(1);
        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), 'api.twilio.com')
                && ($body['ContentSid'] ?? null) === 'HXtemplate123'
                && ($body['From'] ?? null) === 'whatsapp:+14155238886'
                && ($body['To'] ?? null) === 'whatsapp:+263710939310';
        });
    }

    public function test_whatsapp_test_command_sends_via_log_provider(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.provider' => 'log',
        ]);

        Log::shouldReceive('info')->once();

        $this->artisan('whatsapp:test', ['phone' => '+263771234567'])
            ->expectsOutputToContain('Provider: log')
            ->expectsOutputToContain('WhatsApp message sent successfully.')
            ->assertSuccessful();
    }

    public function test_whatsapp_test_command_reports_failure_when_disabled(): void
    {
        config(['services.whatsapp.enabled' => false]);
        Log::shouldReceive('info')->once();

        $this->artisan('whatsapp:test', ['phone' => '+263771234567'])
            ->expectsOutputToContain('Enabled: no')
            ->assertFailed();
    }

    public function test_tenant_storage_prefixes_upload_directory(): void
    {
        $service = app(TenantStorageService::class);

        $this->assertSame('school-5/uploads', $service->scopedDirectory(5, 'uploads'));
        $this->assertSame('school-5/uploads', $service->scopedDirectory(5, 'school-5/uploads'));
        $this->assertSame('schools/5/students/1/docs', $service->scopedDirectory(5, 'schools/5/students/1/docs'));
    }

    public function test_file_upload_service_applies_tenant_prefix(): void
    {
        Storage::fake('public');
        $auth = $this->createAuthenticatedUser();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $result = app(\App\Services\FileUploadService::class)->store(
            $file,
            'avatars',
            'public',
            $auth['school']->id,
        );

        $this->assertStringStartsWith("school-{$auth['school']->id}/avatars/", $result['path']);
    }
}
