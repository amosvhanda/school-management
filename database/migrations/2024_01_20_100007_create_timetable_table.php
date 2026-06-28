<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('room')->nullable();
            $table->string('day'); // Monday, Tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->string('lesson_type')->nullable(); // e.g., "lecture", "practical", "lab"
            $table->timestamps();
            
            $table->index('school_id');
            $table->index(['class_id', 'day']);
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable');
    }
};
