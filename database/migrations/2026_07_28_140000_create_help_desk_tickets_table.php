<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_desk_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->string('requester_name');
            $table->string('requester_email')->nullable();
            $table->string('requester_phone')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'ticket_number']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_desk_tickets');
    }
};
