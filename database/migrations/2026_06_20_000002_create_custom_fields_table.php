<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('entity_type');
            $table->string('name');
            $table->string('slug');
            $table->string('field_type')->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'entity_type', 'slug']);
            $table->index(['school_id', 'entity_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
