<?php

namespace App\Services\Platform;

use App\Models\AuditLog;
use App\Models\NotificationQueue;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealthService
{
    public function snapshot(?int $schoolId = null): array
    {
        $dbOk = $this->checkDatabase();
        $cacheOk = $this->checkCache();
        $storageOk = $this->checkStorage();
        $mailer = (string) config('mail.default', 'log');

        $errorCount = AuditLog::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('action', 'like', '%error%')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $activeUsers = User::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('updated_at', '>=', now()->subDay())
            ->count();

        $pendingJobs = $this->safeCount('jobs');
        $failedJobs = $this->safeCount('failed_jobs');

        $notificationPending = NotificationQueue::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'pending')
            ->count();
        $notificationFailed = NotificationQueue::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'failed')
            ->count();

        $checks = [
            'database' => $dbOk,
            'cache' => $cacheOk,
            'storage' => $storageOk,
        ];
        $degraded = in_array(false, $checks, true);
        $status = ! $dbOk ? 'critical' : ($degraded || $failedJobs > 0 || $notificationFailed > 10 ? 'degraded' : 'healthy');

        return [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'uptime_check' => $dbOk,
            'database' => ['connected' => $dbOk],
            'cache' => [
                'ok' => $cacheOk,
                'driver' => (string) config('cache.default'),
            ],
            'storage' => [
                'writable' => $storageOk,
                'disk' => (string) config('filesystems.default'),
            ],
            'mail' => [
                'mailer' => $mailer,
                'from' => (string) config('mail.from.address'),
            ],
            'usage' => [
                'active_users_24h' => $activeUsers,
                'error_events_24h' => $errorCount,
            ],
            'queues' => [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'notification_pending' => $notificationPending,
                'notification_failed' => $notificationFailed,
                'default_connection' => (string) config('queue.default'),
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'app_env' => (string) config('app.env'),
                'license_enforcement' => (bool) config('license.enforcement', false),
            ],
        ];
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function checkCache(): bool
    {
        try {
            $key = 'platform:health:'.uniqid('', true);
            Cache::put($key, 'ok', 10);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $ok;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function checkStorage(): bool
    {
        try {
            $disk = Storage::disk('local');
            $path = 'health/'.uniqid('probe_', true).'.txt';
            $disk->put($path, 'ok');
            $ok = $disk->exists($path);
            $disk->delete($path);

            return $ok;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function safeCount(string $table): int
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                return 0;
            }

            return (int) DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
