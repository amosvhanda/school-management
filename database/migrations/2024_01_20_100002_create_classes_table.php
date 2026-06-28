<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('name'); // e.g., "Form 3B"
            $table->string('form')->nullable(); // e.g., "Form 3"
            $table->string('school')->nullable();
            $table->integer('capacity')->default(40);
            $table->integer('current_enrollment')->default(0);
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedBigInteger('grade_level_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            
            $table->unique(['school_id', 'name']);
            $table->index('school_id');
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
