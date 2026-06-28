<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Platform\WorkflowEscalationService;
use Illuminate\Console\Command;

class EscalateWorkflowsCommand extends Command
{
    protected $signature = 'workflows:escalate {--school=}';

    protected $description = 'Escalate overdue workflow approvals';

    public function handle(WorkflowEscalationService $service): int
    {
        $schools = $this->option('school')
            ? School::where('id', $this->option('school'))->get()
            : School::where('status', 'active')->get();

        $total = 0;
        foreach ($schools as $school) {
            $count = $service->processEscalations($school->id);
            $total += $count;
            $this->info("School {$school->id}: {$count} workflows escalated");
        }

        $this->info("Total escalations: {$total}");

        return self::SUCCESS;
    }
}
