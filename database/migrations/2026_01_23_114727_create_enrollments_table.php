<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('academic_year');
            $table->date('enrolled_at');
            $table->date('left_at')->nullable();
            $table->string('status')->default('active'); // active, transferred, graduated
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['student_id', 'class_id', 'academic_year']);
            $table->index(['student_id', 'academic_year']);
            $table->index(['class_id', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
