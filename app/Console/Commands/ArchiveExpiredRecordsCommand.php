<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Platform\DataRetentionService;
use Illuminate\Console\Command;

class ArchiveExpiredRecordsCommand extends Command
{
    protected $signature = 'data:archive-expired {--school=}';

    protected $description = 'Archive records past retention policy';

    public function handle(DataRetentionService $service): int
    {
        $schools = $this->option('school')
            ? School::where('id', $this->option('school'))->get()
            : School::where('status', 'active')->get();

        $total = 0;
        foreach ($schools as $school) {
            $count = $service->archiveExpired($school->id);
            $total += $count;
            $this->info("School {$school->id}: {$count} records archived");
        }

        $this->info("Total archived: {$total}");

        return self::SUCCESS;
    }
}
