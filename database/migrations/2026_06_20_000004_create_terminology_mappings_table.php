<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminology_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('system_key');
            $table->string('custom_label');
            $table->string('locale')->default('en');
            $table->timestamps();

            $table->unique(['school_id', 'system_key', 'locale']);
            $table->index(['school_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminology_mappings');
    }
};
