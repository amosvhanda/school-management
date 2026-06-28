<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module');
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('platform')->nullable();
            $table->string('location')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_path')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'module', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
        });

        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('event');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('platform')->nullable();
            $table->string('location')->nullable();
            $table->string('token_name')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'event', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                if (! Schema::hasColumn('exam_results', 'status')) {
                    $table->string('status')->default('draft')->after('remarks');
                }
                if (! Schema::hasColumn('exam_results', 'entered_by')) {
                    $table->foreignId('entered_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('exam_results', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('entered_by')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('exam_results', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }

        if (Schema::hasTable('exams') && ! Schema::hasColumn('exams', 'results_approved_at')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->timestamp('results_approved_at')->nullable()->after('is_published');
                $table->foreignId('results_approved_by')->nullable()->after('results_approved_at')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                if (Schema::hasColumn('exams', 'results_approved_by')) {
                    $table->dropForeign(['results_approved_by']);
                    $table->dropColumn(['results_approved_by', 'results_approved_at']);
                }
            });
        }

        if (Schema::hasTable('exam_results')) {
            Schema::table('exam_results', function (Blueprint $table) {
                foreach (['approved_at', 'approved_by', 'entered_by', 'status'] as $column) {
                    if (Schema::hasColumn('exam_results', $column)) {
                        if ($column === 'approved_by' || $column === 'entered_by') {
                            $table->dropForeign([$column]);
                        }
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('login_history');
        Schema::dropIfExists('audit_logs');
    }
};
