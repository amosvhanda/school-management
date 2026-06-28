<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'repetition_count')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedSmallInteger('repetition_count')->default(0)->after('status');
            });
        }

        if (Schema::hasTable('enrollment_applications') && ! Schema::hasColumn('enrollment_applications', 'student_id')) {
            Schema::table('enrollment_applications', function (Blueprint $table) {
                $table->foreignId('student_id')->nullable()->after('school_id')->constrained('students')->nullOnDelete();
            });
        }

        if (Schema::hasTable('teachers') && ! Schema::hasColumn('teachers', 'employment_type')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->string('employment_type')->default('full_time')->after('status');
                $table->decimal('period_rate', 10, 2)->nullable()->after('base_salary');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'repetition_count')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('repetition_count');
            });
        }

        if (Schema::hasTable('enrollment_applications') && Schema::hasColumn('enrollment_applications', 'student_id')) {
            Schema::table('enrollment_applications', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->dropColumn('student_id');
            });
        }

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'employment_type')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropColumn(['employment_type', 'period_rate']);
            });
        }
    }
};
