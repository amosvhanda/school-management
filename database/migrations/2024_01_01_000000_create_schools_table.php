<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schools must exist before tenant-scoped tables reference school_id.
     */
    public function up(): void
    {
        if (Schema::hasTable('schools')) {
            return;
        }

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('currency_default', 3)->default('USD');
            $table->boolean('currency_locked')->default(false);
            $table->string('academic_year', 9)->nullable();
            $table->string('current_term', 50)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
