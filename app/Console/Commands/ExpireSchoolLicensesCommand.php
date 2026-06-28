<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class ExpireSchoolLicensesCommand extends Command
{
    protected $signature = 'license:expire';

    protected $description = 'Mark school licenses as expired after the grace period';

    public function handle(LicenseService $licenses): int
    {
        $count = $licenses->expireDueLicenses();

        $this->info("Expired {$count} school license(s).");

        return self::SUCCESS;
    }
}
