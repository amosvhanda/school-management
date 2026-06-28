<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('relationship')->nullable(); // e.g., "parent", "guardian", "sibling"
            $table->text('address')->nullable();
            $table->string('national_id')->nullable();
            $table->string('occupation')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('can_receive_notifications')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'email']);
            $table->index(['school_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
