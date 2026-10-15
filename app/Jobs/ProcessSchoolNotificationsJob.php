<?php

namespace App\Jobs;

use App\Jobs\Concerns\BelongsToTenant;
use App\Services\AttendanceNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSchoolNotificationsJob implements ShouldQueue
{
    use BelongsToTenant;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $limit;

    public function __construct(?int $schoolId = null, int $limit = 50)
    {
        $this->schoolId = $schoolId;
        $this->limit = $limit;
        $this->onQueue('notifications');
    }

    public function handle(AttendanceNotificationService $service): void
    {
        $service->processNotificationQueue($this->limit, $this->schoolId);
    }
}
