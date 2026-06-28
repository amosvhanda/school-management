<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('student_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('national_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('class')->nullable();
            $table->string('school')->nullable();
            $table->string('status')->default('active'); // active, inactive, graduated, transferred
            $table->string('previous_school')->nullable();
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('grade_level_id')->nullable();
            $table->string('guardian_first_name')->nullable();
            $table->string('guardian_last_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->timestamps();
            
            $table->unique(['school_id', 'student_number']);
            $table->index('school_id');
            $table->index('class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
