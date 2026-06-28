<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->unsignedBigInteger('payroll_id')->nullable();
            $table->string('type'); // payment, invoice, fee_applied, refund, adjustment, reversal
            $table->string('category')->default('student'); // student, payroll, expense, income
            $table->string('description');
            $table->string('reference')->nullable();
            $table->decimal('debit', 10, 2)->default(0); // Money owed/charged
            $table->decimal('credit', 10, 2)->default(0); // Money paid/received
            $table->decimal('balance', 10, 2); // Running balance
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('completed'); // completed, pending, cancelled
            $table->string('payment_method')->nullable();
            $table->string('invoice_number')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('school_id');
            $table->index('student_id');
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
