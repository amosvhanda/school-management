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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, json, boolean, number
            $table->string('category')->default('general'); // general, academic, notifications, security, appearance
            $table->timestamps();
            
            $table->unique(['school_id', 'key']);
            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
