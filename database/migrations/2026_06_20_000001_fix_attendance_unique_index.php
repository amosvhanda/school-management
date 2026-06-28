<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'date']);
            $table->unique(['student_id', 'date', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'date', 'class_id']);
            $table->unique(['student_id', 'date']);
        });
    }
};
