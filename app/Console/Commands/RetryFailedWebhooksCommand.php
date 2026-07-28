<?php

namespace App\Console\Commands;

use App\Services\Enterprise\EnterpriseWebhookDispatcher;
use Illuminate\Console\Command;

class RetryFailedWebhooksCommand extends Command
{
    protected $signature = 'webhooks:retry-failed {--school= : Limit retries to a school ID} {--limit=50 : Maximum deliveries to retry}';

    protected $description = 'Retry failed enterprise webhook deliveries';

    public function handle(EnterpriseWebhookDispatcher $dispatcher): int
    {
        $schoolId = $this->option('school');
        $limit = max(1, (int) $this->option('limit'));

        $retried = $dispatcher->retryFailed(
            $schoolId !== null ? (int) $schoolId : null,
            $limit,
        );

        $this->info("Queued {$retried} failed webhook delivery retries.");

        return self::SUCCESS;
    }
}
