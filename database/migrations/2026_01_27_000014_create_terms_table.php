<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name'); // e.g., "Term 1", "Term 2", "Term 3"
            $table->string('academic_year', 9); // e.g., "2024-2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(1); // Order within academic year
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'academic_year', 'name']);
            $table->index(['school_id', 'academic_year', 'is_current']);
            $table->index(['school_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
