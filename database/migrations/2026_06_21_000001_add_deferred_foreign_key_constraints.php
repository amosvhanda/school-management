<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add foreign keys deferred because referenced tables are created later in the migration timeline.
     * Safe for MySQL (strict FK order) and SQLite.
     */
    public function up(): void
    {
        $this->addForeignKey('students', 'class_id', 'classes');
        $this->addForeignKey('grades', 'subject_id', 'subjects');
        $this->addForeignKey('attendance', 'subject_id', 'subjects');
        $this->addForeignKey('timetable', 'subject_id', 'subjects');
        $this->addForeignKey('transactions', 'payroll_id', 'payroll');

        if (Schema::hasTable('agent_conversations') && Schema::hasColumn('agent_conversations', 'user_id')) {
            $this->addForeignKey('agent_conversations', 'user_id', 'users');
        }
    }

    public function down(): void
    {
        $this->dropForeignKey('transactions', 'payroll_id');
        $this->dropForeignKey('timetable', 'subject_id');
        $this->dropForeignKey('attendance', 'subject_id');
        $this->dropForeignKey('grades', 'subject_id');
        $this->dropForeignKey('students', 'class_id');

        if (Schema::hasTable('agent_conversations')) {
            $this->dropForeignKey('agent_conversations', 'user_id');
        }
    }

    private function addForeignKey(string $table, string $column, string $references): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasTable($references) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $references) {
                $blueprint->foreign($column)->references('id')->on($references)->nullOnDelete();
            });
        } catch (\Throwable) {
            // FK may already exist from an earlier migration run.
        }
    }

    private function dropForeignKey(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });
        } catch (\Throwable) {
            //
        }
    }
};
