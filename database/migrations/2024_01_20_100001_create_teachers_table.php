<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('employee_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('subject')->nullable();
            $table->string('department')->nullable();
            $table->string('qualification')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('status')->default('active');
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('USD');
            $table->json('allowances')->nullable();
            $table->json('deductions')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('payment_method')->default('bank_transfer');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['school_id', 'employee_id']);
            $table->unique(['school_id', 'email']);
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
