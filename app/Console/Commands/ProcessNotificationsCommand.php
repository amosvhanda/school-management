<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSchoolNotificationsJob;
use App\Models\NotificationQueue;
use App\Models\School;
use App\Services\AttendanceNotificationService;
use Illuminate\Console\Command;

class ProcessNotificationsCommand extends Command
{
    protected $signature = 'notifications:process
                            {--limit=50 : Maximum notifications to process per school}
                            {--school= : Process a single school id}
                            {--sync : Process inline instead of dispatching queued jobs}';

    protected $description = 'Process pending outbound notification queue entries (email/SMS/WhatsApp) with tenant context';

    public function handle(AttendanceNotificationService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $schoolFilter = $this->option('school') ? (int) $this->option('school') : null;
        $sync = (bool) $this->option('sync');

        $schoolIds = NotificationQueue::query()
            ->where('status', 'pending')
            ->when($schoolFilter, fn ($q) => $q->where('school_id', $schoolFilter))
            ->whereNotNull('school_id')
            ->distinct()
            ->pluck('school_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($schoolIds === []) {
            $this->info('No pending notifications.');

            return self::SUCCESS;
        }

        $dispatched = 0;
        $processed = 0;

        foreach ($schoolIds as $schoolId) {
            if (! School::query()->whereKey($schoolId)->exists()) {
                continue;
            }

            if ($sync || config('queue.default') === 'sync') {
                $processed += $service->processNotificationQueue($limit, $schoolId);
            } else {
                ProcessSchoolNotificationsJob::dispatch($schoolId, $limit);
                $dispatched++;
            }
        }

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} tenant notification job(s).");
        }

        if ($processed > 0 || $sync) {
            $this->info("Processed {$processed} notification(s) inline.");
        }

        return self::SUCCESS;
    }
}
