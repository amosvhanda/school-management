<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, important, warning, success
            $table->string('target_audience')->default('all'); // all, students, parents, teachers, staff
            $table->date('date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index('school_id');
            $table->index(['school_id', 'is_active', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
