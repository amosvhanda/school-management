<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'mother_tongue')) {
                $table->string('mother_tongue')->nullable()->after('caste');
            }
            if (! Schema::hasColumn('students', 'languages_known')) {
                $table->string('languages_known')->nullable()->after('mother_tongue');
            }
            if (! Schema::hasColumn('students', 'siblings')) {
                $table->json('siblings')->nullable()->after('languages_known');
            }
        });

        Schema::table('student_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('student_documents', 'is_favourite')) {
                $table->boolean('is_favourite')->default(false)->after('size');
            }
            if (! Schema::hasColumn('student_documents', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('is_favourite');
            }
            if (! Schema::hasColumn('student_documents', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['mother_tongue', 'languages_known', 'siblings'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('student_documents', function (Blueprint $table) {
            foreach (['is_favourite', 'verified_at', 'expires_at'] as $col) {
                if (Schema::hasColumn('student_documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
