<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('subject');
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('due_date');
            $table->decimal('total_marks', 5, 2)->default(100);
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->integer('submissions_count')->default(0);
            $table->text('instructions')->nullable();
            $table->string('attachment_url')->nullable();
            $table->timestamps();
            
            $table->index('school_id');
            $table->index(['class_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
