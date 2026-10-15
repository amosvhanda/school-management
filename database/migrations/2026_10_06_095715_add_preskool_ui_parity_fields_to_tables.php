<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Student Profile UI Extensions
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'roll_number')) {
                $table->string('roll_number')->nullable()->after('student_number');
            }
            if (!Schema::hasColumn('students', 'blood_group')) {
                $table->string('blood_group')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('students', 'passport_number')) {
                $table->string('passport_number')->nullable()->after('national_id');
            }
            if (!Schema::hasColumn('students', 'social_links')) {
                $table->json('social_links')->nullable()->after('address');
            }
        });

        // 2. Teacher Profile UI Extensions
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'blood_group')) {
                $table->string('blood_group')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('teachers', 'passport_number')) {
                $table->string('passport_number')->nullable()->after('national_id');
            }
            if (!Schema::hasColumn('teachers', 'social_links')) {
                $table->json('social_links')->nullable()->after('address');
            }
            if (!Schema::hasColumn('teachers', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('teachers', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
        });

        // 3. Grading Scale Points (for GPA Calculation)
        Schema::table('grading_scales', function (Blueprint $table) {
            if (!Schema::hasColumn('grading_scales', 'grade_point')) {
                $table->decimal('grade_point', 4, 2)->nullable()->default(0.00)->after('grade');
            }
        });

        // 4. Online Lessons / Virtual Class API Metadata
        Schema::table('online_lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('online_lessons', 'provider')) {
                $table->string('provider')->default('zoom')->after('lesson_type'); // zoom, google_meet, teams
            }
            if (!Schema::hasColumn('online_lessons', 'meeting_id')) {
                $table->string('meeting_id')->nullable()->after('meeting_url');
            }
            if (!Schema::hasColumn('online_lessons', 'passcode')) {
                $table->string('passcode')->nullable()->after('meeting_id');
            }
        });

        // 5. Library Book Shelf Location
        Schema::table('library_books', function (Blueprint $table) {
            if (!Schema::hasColumn('library_books', 'rack_number')) {
                $table->string('rack_number')->nullable()->after('category');
            }
            if (!Schema::hasColumn('library_books', 'shelf_number')) {
                $table->string('shelf_number')->nullable()->after('rack_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['roll_number', 'blood_group', 'passport_number', 'social_links']);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['blood_group', 'passport_number', 'social_links', 'emergency_contact_name', 'emergency_contact_phone']);
        });

        Schema::table('grading_scales', function (Blueprint $table) {
            $table->dropColumn(['grade_point']);
        });

        Schema::table('online_lessons', function (Blueprint $table) {
            $table->dropColumn(['provider', 'meeting_id', 'passcode']);
        });

        Schema::table('library_books', function (Blueprint $table) {
            $table->dropColumn(['rack_number', 'shelf_number']);
        });
    }
};
