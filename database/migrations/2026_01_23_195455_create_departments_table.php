<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('head_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
