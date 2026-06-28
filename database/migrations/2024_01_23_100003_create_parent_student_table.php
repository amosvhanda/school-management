<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('relationship')->default('parent'); // parent, guardian, etc.
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            
            $table->unique(['parent_id', 'student_id']);
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
    }
};
