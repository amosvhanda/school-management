<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('destination')->nullable();
            $table->date('trip_date');
            $table->date('return_date')->nullable();
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('open_for_registration')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
        });

        Schema::create('school_trip_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('school_trip_id')->constrained('school_trips')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('status', 30)->default('enrolled');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(['school_trip_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_trip_enrollments');
        Schema::dropIfExists('school_trips');
    }
};
