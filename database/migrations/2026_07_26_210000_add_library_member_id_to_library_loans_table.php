<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_loans', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('library_loans', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreignId('library_member_id')
                ->nullable()
                ->after('book_id')
                ->constrained('library_members')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('library_loans', function (Blueprint $table) {
            $table->dropForeign(['library_member_id']);
            $table->dropColumn('library_member_id');
            $table->dropForeign(['student_id']);
        });

        Schema::table('library_loans', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }
};
