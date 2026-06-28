<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('grade'); // A, B, C, D, E, F, etc.
            $table->decimal('min_score', 5, 2); // e.g., 80.00
            $table->decimal('max_score', 5, 2); // e.g., 100.00
            $table->string('description')->nullable(); // e.g., "Excellent", "Good"
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'grade']);
            $table->index(['school_id', 'min_score', 'max_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
    }
};
