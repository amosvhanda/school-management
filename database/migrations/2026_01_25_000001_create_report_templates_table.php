<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('frequency')->nullable();
            $table->json('recipients')->nullable();
            $table->json('parameters')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
