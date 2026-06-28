<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('name'); // e.g., "Mid-Term Exam", "Final Exam"
            $table->text('description')->nullable();
            $table->date('exam_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('total_marks', 8, 2)->default(100);
            $table->decimal('passing_marks', 8, 2)->nullable();
            $table->string('academic_year', 9);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'term_id', 'grade_level_id']);
            $table->index(['school_id', 'academic_year']);
            $table->index(['school_id', 'exam_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
