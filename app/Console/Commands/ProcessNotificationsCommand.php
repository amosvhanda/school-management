<?php

namespace App\Console\Commands;

use App\Services\AttendanceNotificationService;
use Illuminate\Console\Command;

class ProcessNotificationsCommand extends Command
{
    protected $signature = 'notifications:process {--limit=50 : Maximum notifications to process}';

    protected $description = 'Process pending outbound notification queue entries (email/SMS)';

    public function handle(AttendanceNotificationService $service): int
    {
        $limit = (int) $this->option('limit');
        $processed = $service->processNotificationQueue($limit);

        $this->info("Processed {$processed} notification(s).");

        return self::SUCCESS;
    }
}
