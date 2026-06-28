<?php

namespace App\Services\Platform;

use App\Models\ArchivedRecord;
use App\Models\RetentionPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataRetentionService
{
    public function archiveExpired(int $schoolId): int
    {
        $policies = RetentionPolicy::where('school_id', $schoolId)->where('is_active', true)->get();
        $archived = 0;

        foreach ($policies as $policy) {
            $table = $this->tableForModule($policy->module);
            if (! $table || ! Schema::hasTable($table)) {
                continue;
            }

            $cutoff = now()->subYears($policy->retain_years);

            $rows = DB::table($table)
                ->where('school_id', $schoolId)
                ->where('created_at', '<', $cutoff)
                ->limit(100)
                ->get();

            foreach ($rows as $row) {
                ArchivedRecord::create([
                    'school_id' => $schoolId,
                    'source_table' => $table,
                    'source_id' => $row->id,
                    'snapshot' => (array) $row,
                    'archived_at' => now(),
                ]);

                DB::table($table)->where('id', $row->id)->delete();
                $archived++;
            }
        }

        return $archived;
    }

    protected function tableForModule(string $module): ?string
    {
        return match ($module) {
            'audit' => 'audit_logs',
            'attendance' => 'attendances',
            'disciplinary' => 'disciplinary_records',
            default => null,
        };
    }
}
