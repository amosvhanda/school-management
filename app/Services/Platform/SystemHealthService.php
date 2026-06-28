<?php

namespace App\Services\Platform;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SystemHealthService
{
    public function snapshot(?int $schoolId = null): array
    {
        $dbOk = $this->checkDatabase();
        $errorCount = AuditLog::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('action', 'like', '%error%')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $activeUsers = User::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('updated_at', '>=', now()->subDay())
            ->count();

        return [
            'status' => $dbOk ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'uptime_check' => $dbOk,
            'database' => ['connected' => $dbOk],
            'usage' => [
                'active_users_24h' => $activeUsers,
                'error_events_24h' => $errorCount,
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
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
}
