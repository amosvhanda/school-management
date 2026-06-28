<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add foreign key constraints for columns that reference tables created later.
     * This migration runs after all base tables are created.
     */
    public function up(): void
    {
        // Add foreign key for users.school_id (if not already constrained)
        if (Schema::hasTable('users') && Schema::hasTable('schools') && Schema::hasColumn('users', 'school_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }

        // Add foreign keys for students table
        if (Schema::hasTable('students') && Schema::hasTable('grade_levels') && Schema::hasColumn('students', 'grade_level_id')) {
            try {
                Schema::table('students', function (Blueprint $table) {
                    $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }

        // Add foreign keys for classes table
        if (Schema::hasTable('classes') && Schema::hasTable('grade_levels') && Schema::hasColumn('classes', 'grade_level_id')) {
            try {
                Schema::table('classes', function (Blueprint $table) {
                    $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }

        if (Schema::hasTable('classes') && Schema::hasTable('rooms') && Schema::hasColumn('classes', 'room_id')) {
            try {
                Schema::table('classes', function (Blueprint $table) {
                    $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }

        // Add foreign keys for grades table
        if (Schema::hasTable('grades') && Schema::hasTable('grade_levels') && Schema::hasColumn('grades', 'grade_level_id')) {
            try {
                Schema::table('grades', function (Blueprint $table) {
                    $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }

        if (Schema::hasTable('grades') && Schema::hasTable('grading_scales') && Schema::hasColumn('grades', 'grading_scale_id')) {
            try {
                Schema::table('grades', function (Blueprint $table) {
                    $table->foreign('grading_scale_id')->references('id')->on('grading_scales')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }

        // Add foreign key for timetable.room_id
        if (Schema::hasTable('timetable') && Schema::hasTable('rooms') && Schema::hasColumn('timetable', 'room_id')) {
            try {
                Schema::table('timetable', function (Blueprint $table) {
                    $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist, ignore
            }
        }
    }

    public function down(): void
    {
        // Drop foreign keys in reverse order
        if (Schema::hasTable('timetable') && Schema::hasColumn('timetable', 'room_id')) {
            Schema::table('timetable', function (Blueprint $table) {
                $table->dropForeign(['room_id']);
            });
        }

        if (Schema::hasTable('grades')) {
            if (Schema::hasColumn('grades', 'grading_scale_id')) {
                Schema::table('grades', function (Blueprint $table) {
                    $table->dropForeign(['grading_scale_id']);
                });
            }
            if (Schema::hasColumn('grades', 'grade_level_id')) {
                Schema::table('grades', function (Blueprint $table) {
                    $table->dropForeign(['grade_level_id']);
                });
            }
        }

        if (Schema::hasTable('classes')) {
            if (Schema::hasColumn('classes', 'room_id')) {
                Schema::table('classes', function (Blueprint $table) {
                    $table->dropForeign(['room_id']);
                });
            }
            if (Schema::hasColumn('classes', 'grade_level_id')) {
                Schema::table('classes', function (Blueprint $table) {
                    $table->dropForeign(['grade_level_id']);
                });
            }
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'grade_level_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['grade_level_id']);
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
            });
        }
    }

};
