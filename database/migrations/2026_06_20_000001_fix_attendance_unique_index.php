<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicateAttendance(['student_id', 'date', 'class_id']);
        $this->dropUniqueIndex('attendance', ['student_id', 'date']);

        Schema::table('attendance', function (Blueprint $table) {
            $table->unique(['student_id', 'date', 'class_id']);
        });
    }

    public function down(): void
    {
        // Older unique key is only (student_id, date). Collapse any same-day
        // multi-class rows before recreating it so refresh/rollback cannot fail.
        $this->deduplicateAttendance(['student_id', 'date']);
        $this->dropUniqueIndex('attendance', ['student_id', 'date', 'class_id']);

        Schema::table('attendance', function (Blueprint $table) {
            $table->unique(['student_id', 'date']);
        });
    }

    /**
     * Keep the newest row for each unique key combination; delete the rest.
     *
     * @param  list<string>  $columns
     */
    private function deduplicateAttendance(array $columns): void
    {
        if (! Schema::hasTable('attendance')) {
            return;
        }

        $columnList = implode(', ', $columns);

        DB::statement("
            DELETE FROM attendance
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MAX(id) AS id
                    FROM attendance
                    GROUP BY {$columnList}
                ) AS keepers
            )
        ");
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropUniqueIndex(string $table, array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropUnique($columns);
            });
        } catch (\Throwable) {
            // Index already absent (partial refresh / sqlite rename quirks).
        }
    }
};
