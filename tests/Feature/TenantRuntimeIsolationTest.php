<?php

namespace Tests\Feature;

use App\Jobs\ProcessSchoolNotificationsJob;
use App\Models\NotificationQueue;
use App\Models\Student;
use App\Services\AttendanceNotificationService;
use App\Services\SchoolSettingsService;
use App\Services\Tenancy\SchoolMessagingService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TenantRuntimeIsolationTest extends TestCase
{
    public function test_license_middleware_blocks_when_enforcement_enabled_and_unlicensed(): void
    {
        config(['license.enforcement' => true]);

        $auth = $this->createAuthenticatedUser();
        $auth['school']->update([
            'license_status' => 'none',
            'license_expires_at' => null,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/students')
            ->assertStatus(402)
            ->assertJsonPath('code', 'license_none');
    }

    public function test_school_messaging_prefers_school_sms_credentials(): void
    {
        $auth = $this->createAuthenticatedUser();
        $school = $auth['school'];
        $settings = app(SchoolSettingsService::class);

        $settings->set($school, 'notifications', 'sms_notices', true, 'boolean');
        $settings->set($school, 'sms', 'enabled', true, 'boolean');
        $settings->set($school, 'sms', 'provider', 'log');
        $settings->set($school, 'sms', 'twilio_from', '+263771111111');

        config(['services.sms.enabled' => false]);

        $config = app(SchoolMessagingService::class)->smsConfig($school);

        $this->assertTrue($config['enabled']);
        $this->assertSame('school', $config['source']);
        $this->assertSame('log', $config['provider']);
        $this->assertSame('+263771111111', $config['twilio_from']);
    }

    public function test_notifications_process_dispatches_per_school_jobs_when_async(): void
    {
        config(['queue.default' => 'database']);
        Bus::fake();

        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        NotificationQueue::create([
            'school_id' => $auth['school']->id,
            'type' => 'test',
            'notifiable_type' => Student::class,
            'notifiable_id' => $student->id,
            'channel' => 'sms',
            'recipient_phone' => '+263770000000',
            'message' => 'Hello',
            'status' => 'pending',
        ]);

        $this->artisan('notifications:process', ['--limit' => 10])
            ->assertSuccessful();

        Bus::assertDispatched(ProcessSchoolNotificationsJob::class, function (ProcessSchoolNotificationsJob $job) use ($auth) {
            return $job->schoolId === $auth['school']->id;
        });
    }

    public function test_notification_queue_uses_school_whatsapp_config(): void
    {
        $auth = $this->createAuthenticatedUser();
        $school = $auth['school'];
        $settings = app(SchoolSettingsService::class);

        $settings->set($school, 'notifications', 'whatsapp_notices', true, 'boolean');
        $settings->set($school, 'whatsapp', 'enabled', true, 'boolean');
        $settings->set($school, 'whatsapp', 'provider', 'log');

        config(['services.whatsapp.enabled' => false]);

        $student = Student::factory()->create(['school_id' => $school->id]);
        $notification = NotificationQueue::create([
            'school_id' => $school->id,
            'type' => 'test_whatsapp',
            'notifiable_type' => Student::class,
            'notifiable_id' => $student->id,
            'channel' => 'whatsapp',
            'recipient_phone' => '+263770000001',
            'message' => 'WA hello',
            'status' => 'pending',
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $processed = app(AttendanceNotificationService::class)->processNotificationQueue(10, $school->id);

        $this->assertSame(1, $processed);
        $this->assertSame('sent', $notification->fresh()->status);
    }

    public function test_system_health_includes_queue_storage_and_license_flags(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/platform/system/health')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'database' => ['connected'],
                    'cache' => ['ok', 'driver'],
                    'storage' => ['writable', 'disk'],
                    'mail' => ['mailer'],
                    'queues' => [
                        'pending_jobs',
                        'failed_jobs',
                        'notification_pending',
                        'notification_failed',
                    ],
                    'server' => [
                        'php_version',
                        'license_enforcement',
                        'app_env',
                    ],
                ],
            ]);
    }
}
