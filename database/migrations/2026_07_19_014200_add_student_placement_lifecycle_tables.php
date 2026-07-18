<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('streams')) {
            Schema::create('streams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        if (! Schema::hasTable('houses')) {
            Schema::create('houses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('color')->nullable();
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                if (! Schema::hasColumn('classes', 'stream_id')) {
                    $table->foreignId('stream_id')->nullable()->after('grade_level_id')->constrained('streams')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (! Schema::hasColumn('students', 'stream_id')) {
                    $table->foreignId('stream_id')->nullable()->after('class_id')->constrained('streams')->nullOnDelete();
                }
                if (! Schema::hasColumn('students', 'house_id')) {
                    $table->foreignId('house_id')->nullable()->after('stream_id')->constrained('houses')->nullOnDelete();
                }
                if (! Schema::hasColumn('students', 'status_reason')) {
                    $table->string('status_reason')->nullable()->after('status');
                }
                if (! Schema::hasColumn('students', 'status_changed_at')) {
                    $table->timestamp('status_changed_at')->nullable()->after('status_reason');
                }
                if (! Schema::hasColumn('students', 'exited_at')) {
                    $table->date('exited_at')->nullable()->after('status_changed_at');
                }
            });
        }

        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                if (! Schema::hasColumn('enrollments', 'stream_id')) {
                    $table->foreignId('stream_id')->nullable()->after('class_id')->constrained('streams')->nullOnDelete();
                }
                if (! Schema::hasColumn('enrollments', 'house_id')) {
                    $table->foreignId('house_id')->nullable()->after('stream_id')->constrained('houses')->nullOnDelete();
                }
                if (! Schema::hasColumn('enrollments', 'reason')) {
                    $table->string('reason')->nullable()->after('status');
                }
                if (! Schema::hasColumn('enrollments', 'changed_by')) {
                    $table->foreignId('changed_by')->nullable()->after('reason')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('grade_level_subjects')) {
            Schema::create('grade_level_subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
                $table->boolean('is_core')->default(true);
                $table->timestamps();
                $table->unique(['grade_level_id', 'subject_id', 'stream_id'], 'grade_stream_subject_unique');
            });
        }

        if (! Schema::hasTable('student_status_events')) {
            Schema::create('student_status_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->string('action');
                $table->text('reason')->nullable();
                $table->date('effective_date')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_status_events');
        Schema::dropIfExists('grade_level_subjects');

        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                if (Schema::hasColumn('enrollments', 'changed_by')) {
                    $table->dropConstrainedForeignId('changed_by');
                }
                if (Schema::hasColumn('enrollments', 'reason')) {
                    $table->dropColumn('reason');
                }
                if (Schema::hasColumn('enrollments', 'house_id')) {
                    $table->dropConstrainedForeignId('house_id');
                }
                if (Schema::hasColumn('enrollments', 'stream_id')) {
                    $table->dropConstrainedForeignId('stream_id');
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                foreach (['exited_at', 'status_changed_at', 'status_reason'] as $col) {
                    if (Schema::hasColumn('students', $col)) {
                        $table->dropColumn($col);
                    }
                }
                if (Schema::hasColumn('students', 'house_id')) {
                    $table->dropConstrainedForeignId('house_id');
                }
                if (Schema::hasColumn('students', 'stream_id')) {
                    $table->dropConstrainedForeignId('stream_id');
                }
            });
        }

        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'stream_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('stream_id');
            });
        }

        Schema::dropIfExists('houses');
        Schema::dropIfExists('streams');
    }
};
