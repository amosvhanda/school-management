<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('role')->nullable(); // e.g., "class_teacher", "subject_teacher", "form_teacher"
            $table->date('assigned_at')->default(now());
            $table->date('assigned_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'teacher_id']);
            $table->index(['school_id', 'class_id']);
            $table->index(['school_id', 'grade_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
