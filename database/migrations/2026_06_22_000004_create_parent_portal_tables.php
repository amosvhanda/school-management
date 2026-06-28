<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parent_notifications')) {
            Schema::create('parent_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->string('type');
                $table->string('title');
                $table->text('body');
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['parent_user_id', 'read_at']);
                $table->index(['school_id', 'type']);
            });
        }

        if (! Schema::hasTable('disciplinary_records')) {
            Schema::create('disciplinary_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->date('incident_date');
                $table->string('category');
                $table->string('severity')->default('minor');
                $table->text('description');
                $table->text('action_taken')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('parent_notified')->default(false);
                $table->timestamp('parent_notified_at')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'incident_date']);
            });
        }

        if (! Schema::hasTable('communication_threads')) {
            Schema::create('communication_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('subject');
                $table->string('status')->default('open');
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                $table->index(['parent_user_id', 'status']);
            });
        }

        if (! Schema::hasTable('communication_messages')) {
            Schema::create('communication_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('communication_threads')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['thread_id', 'created_at']);
            });
        }

        if (Schema::hasTable('notification_queue') && ! Schema::hasColumn('notification_queue', 'parent_user_id')) {
            Schema::table('notification_queue', function (Blueprint $table) {
                $table->foreignId('parent_user_id')->nullable()->after('guardian_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_queue') && Schema::hasColumn('notification_queue', 'parent_user_id')) {
            Schema::table('notification_queue', function (Blueprint $table) {
                $table->dropForeign(['parent_user_id']);
                $table->dropColumn('parent_user_id');
            });
        }

        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('communication_threads');
        Schema::dropIfExists('disciplinary_records');
        Schema::dropIfExists('parent_notifications');
    }
};
