<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->decimal('marks_obtained', 8, 2);
            $table->decimal('total_marks', 8, 2);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade')->nullable(); // From grading scale
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['test_id', 'student_id']);
            $table->index(['school_id', 'test_id']);
            $table->index(['school_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
