<?php

namespace App\Services\Tenancy;

use App\Models\School;
use App\Models\SchoolBackup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SchoolBackupService
{
    /**
     * @var list<string>
     */
    private array $excludedTables = [
        'migrations',
        'jobs',
        'job_batches',
        'failed_jobs',
        'cache',
        'cache_locks',
        'sessions',
        'password_reset_tokens',
        'personal_access_tokens',
        'school_backups',
    ];

    public function create(School $school, ?User $creator = null): SchoolBackup
    {
        $tables = $this->discoverSchoolScopedTables();
        $payload = [
            'school_id' => $school->id,
            'exported_at' => now()->toIso8601String(),
            'tables' => [],
        ];

        $rowCount = 0;

        foreach ($tables as $table) {
            $rows = DB::table($table)
                ->where('school_id', $school->id)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $payload['tables'][$table] = $rows;
            $rowCount += count($rows);
        }

        $relativePath = "backups/school-{$school->id}/".now()->format('Ymd_His').'.json';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::disk('local')->put($relativePath, $json);

        return SchoolBackup::create([
            'school_id' => $school->id,
            'path' => $relativePath,
            'status' => 'completed',
            'table_count' => count($tables),
            'row_count' => $rowCount,
            'file_size' => strlen((string) $json),
            'created_by' => $creator?->id,
        ]);
    }

    public function restore(SchoolBackup $backup): SchoolBackup
    {
        if ($backup->status !== 'completed') {
            throw ValidationException::withMessages([
                'backup' => ['Only completed backups can be restored.'],
            ]);
        }

        if (! Storage::disk('local')->exists($backup->path)) {
            throw ValidationException::withMessages([
                'backup' => ['Backup file is missing from storage.'],
            ]);
        }

        $payload = json_decode((string) Storage::disk('local')->get($backup->path), true);

        if (! is_array($payload) || ! isset($payload['tables']) || ! is_array($payload['tables'])) {
            throw ValidationException::withMessages([
                'backup' => ['Backup file is invalid or corrupted.'],
            ]);
        }

        if ((int) ($payload['school_id'] ?? 0) !== (int) $backup->school_id) {
            throw ValidationException::withMessages([
                'backup' => ['Backup does not belong to this school.'],
            ]);
        }

        DB::transaction(function () use ($backup, $payload) {
            $this->withoutForeignKeyChecks(function () use ($backup, $payload) {
                foreach (array_keys($payload['tables']) as $table) {
                    if (! $this->isSchoolScopedTable($table)) {
                        continue;
                    }

                    DB::table($table)->where('school_id', $backup->school_id)->delete();
                }

                foreach ($payload['tables'] as $table => $rows) {
                    if (! $this->isSchoolScopedTable($table) || ! is_array($rows)) {
                        continue;
                    }

                    foreach (array_chunk($rows, 200) as $chunk) {
                        if ($chunk !== []) {
                            DB::table($table)->insert($chunk);
                        }
                    }
                }
            });

            $backup->update(['restored_at' => now()]);
        });

        return $backup->fresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForSchool(School $school): array
    {
        return SchoolBackup::query()
            ->where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (SchoolBackup $backup) => $this->mapBackup($backup))
            ->all();
    }

    /**
     * @return list<string>
     */
    public function discoverSchoolScopedTables(): array
    {
        $tables = [];

        foreach (Schema::getTables() as $table) {
            $name = (string) ($table['name'] ?? '');
            if ($this->isSchoolScopedTable($name)) {
                $tables[] = $name;
            }
        }

        sort($tables);

        return $tables;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapBackup(SchoolBackup $backup): array
    {
        return [
            'id' => $backup->id,
            'school_id' => $backup->school_id,
            'path' => $backup->path,
            'status' => $backup->status,
            'table_count' => $backup->table_count,
            'row_count' => $backup->row_count,
            'file_size' => $backup->file_size,
            'created_by' => $backup->created_by,
            'created_at' => $backup->created_at?->toIso8601String(),
            'restored_at' => $backup->restored_at?->toIso8601String(),
        ];
    }

    private function isSchoolScopedTable(string $table): bool
    {
        if ($table === '' || in_array($table, $this->excludedTables, true)) {
            return false;
        }

        return Schema::hasColumn($table, 'school_id');
    }

    private function withoutForeignKeyChecks(callable $callback): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            try {
                $callback();
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                $callback();
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return;
        }

        $callback();
    }
}
