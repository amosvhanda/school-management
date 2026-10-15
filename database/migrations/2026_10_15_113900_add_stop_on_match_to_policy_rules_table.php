<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('policy_rules')) {
            return;
        }

        Schema::table('policy_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('policy_rules', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('policy_rules', 'stop_on_match')) {
                $table->boolean('stop_on_match')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('policy_rules', 'last_triggered_at')) {
                $table->timestamp('last_triggered_at')->nullable()->after('stop_on_match');
            }
            if (! Schema::hasColumn('policy_rules', 'execution_count')) {
                $table->unsignedInteger('execution_count')->default(0)->after('last_triggered_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('policy_rules')) {
            return;
        }

        Schema::table('policy_rules', function (Blueprint $table) {
            $columns = collect(['description', 'stop_on_match', 'last_triggered_at', 'execution_count'])
                ->filter(fn (string $column) => Schema::hasColumn('policy_rules', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
