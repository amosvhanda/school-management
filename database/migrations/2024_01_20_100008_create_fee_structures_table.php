<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('class_name'); // Denormalized for display
            $table->string('category'); // Tuition Fees, Development Levy, Exam Fees, etc.
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            
            $table->index('school_id');
            $table->index('class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
