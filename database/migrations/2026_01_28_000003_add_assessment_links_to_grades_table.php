<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('test_id')->nullable()->after('student_id')->constrained('tests')->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->after('test_id')->constrained('assignments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignment_id');
            $table->dropConstrainedForeignId('test_id');
        });
    }
};
