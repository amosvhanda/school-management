<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->string('name'); // e.g., "Chapter 5 Test", "Quiz 1"
            $table->text('description')->nullable();
            $table->date('test_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('total_marks', 8, 2)->default(100);
            $table->decimal('passing_marks', 8, 2)->nullable();
            $table->string('academic_year', 9);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'class_id', 'subject_id']);
            $table->index(['school_id', 'teacher_id']);
            $table->index(['school_id', 'test_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
