<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('base_salary', 12, 2);
            $table->decimal('gross_salary', 12, 2);
            $table->json('allowances')->nullable(); // Detailed allowances breakdown
            $table->json('deductions')->nullable(); // Detailed deductions breakdown
            $table->decimal('allowances_total', 12, 2)->default(0);
            $table->decimal('deductions_total', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending'); // pending, processed, paid, cancelled
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // How payment was made
            $table->string('payment_reference')->nullable(); // Payment reference number
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['school_id', 'teacher_id', 'month', 'year']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll');
    }
};
