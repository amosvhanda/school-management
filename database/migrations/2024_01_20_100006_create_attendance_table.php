<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('lesson_type')->nullable();
            $table->date('date');
            $table->string('status'); // present, absent, late, excused
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('parent_notified')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['student_id', 'date']);
            $table->index('school_id');
            $table->index(['class_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
