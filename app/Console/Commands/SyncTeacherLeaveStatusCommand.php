<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\TeacherLeaveStatusService;
use Illuminate\Console\Command;

class SyncTeacherLeaveStatusCommand extends Command
{
    protected $signature = 'hr:sync-teacher-leave-status {--school=}';

    protected $description = 'Mark teachers on leave for today and restore those whose leave has ended';

    public function handle(TeacherLeaveStatusService $service): int
    {
        $schools = $this->option('school')
            ? School::where('id', $this->option('school'))->get()
            : School::where('status', 'active')->get();

        $marked = 0;
        $restored = 0;

        foreach ($schools as $school) {
            $result = $service->sync((int) $school->id);
            $marked += $result['marked_on_leave'];
            $restored += $result['restored_active'];
            $this->info(
                "School {$school->id}: {$result['marked_on_leave']} on leave, {$result['restored_active']} restored"
            );
        }

        $this->info("Total marked on leave: {$marked}; restored active: {$restored}");

        return self::SUCCESS;
    }
}
