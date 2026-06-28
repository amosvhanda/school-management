<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Platform\FeePenaltyService;
use App\Services\Platform\WorkflowEscalationService;
use Illuminate\Console\Command;

class ApplyFeePenaltiesCommand extends Command
{
    protected $signature = 'fees:apply-penalties {--school=}';

    protected $description = 'Apply automated late fee penalties';

    public function handle(FeePenaltyService $service): int
    {
        $schools = $this->option('school')
            ? School::where('id', $this->option('school'))->get()
            : School::where('status', 'active')->get();

        $total = 0;
        foreach ($schools as $school) {
            $count = $service->applyPenalties($school->id);
            $total += $count;
            $this->info("School {$school->id}: {$count} penalties applied");
        }

        $this->info("Total penalties applied: {$total}");

        return self::SUCCESS;
    }
}
