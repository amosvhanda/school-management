<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('type'); // e.g., "attendance_absence", "invoice_created", "payment_received"
            $table->morphs('notifiable'); // polymorphic: student, guardian, teacher, etc.
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            $table->string('channel'); // "email", "sms", "both"
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->text('subject')->nullable();
            $table->text('message');
            $table->json('data')->nullable(); // Additional data for the notification
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['school_id', 'status', 'created_at']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_queue');
    }
};
