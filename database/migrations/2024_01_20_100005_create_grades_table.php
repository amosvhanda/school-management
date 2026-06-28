<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('subject');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('assessment_type')->default('test'); // test, assignment, exam, project
            $table->decimal('score', 5, 2);
            $table->decimal('total', 5, 2)->default(100);
            $table->string('grade')->nullable(); // A, B, C, D, E, F
            $table->string('term')->nullable(); // Term 1, Term 2, Term 3
            $table->integer('year')->nullable();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedBigInteger('grade_level_id')->nullable();
            $table->unsignedBigInteger('grading_scale_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index('school_id');
            $table->index('student_id');
            $table->index(['student_id', 'year', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
