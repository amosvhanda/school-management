<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('holiday_programs')) {
            return;
        }

        Schema::create('holiday_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('academic_year')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
        });

        Schema::create('holiday_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('holiday_program_id')->constrained('holiday_programs')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('status')->default('enrolled');
            $table->timestamp('enrolled_at');
            $table->timestamps();

            $table->unique(['holiday_program_id', 'student_id']);
        });

        Schema::create('holiday_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('holiday_program_id')->constrained('holiday_programs')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['holiday_program_id', 'student_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_attendance');
        Schema::dropIfExists('holiday_enrollments');
        Schema::dropIfExists('holiday_programs');
    }
};
