<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name'); // e.g., "Room 101", "Lab A", "Library"
            $table->string('code')->nullable(); // e.g., "R101", "LAB-A"
            $table->string('type')->nullable(); // e.g., "classroom", "laboratory", "library", "hall"
            $table->integer('capacity')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable(); // e.g., "Building A, Floor 2"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->unique(['school_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
