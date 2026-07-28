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
