<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('marked_by');
            }
            if (! Schema::hasColumn('attendance', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('attendance', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->date('date');
            $table->string('period')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'class_id', 'date', 'subject_id', 'period'], 'attendance_sessions_unique');
        });

        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('title');
            $table->string('plan_type')->default('lesson'); // lesson, weekly, term, topic
            $table->date('planned_date')->nullable();
            $table->string('topic')->nullable();
            $table->text('objectives')->nullable();
            $table->text('outcomes')->nullable();
            $table->text('activities')->nullable();
            $table->text('resources')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('syllabus_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('title');
            $table->string('chapter')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('objectives')->nullable();
            $table->string('status')->default('planned'); // planned, in_progress, completed
            $table->unsignedTinyInteger('coverage_percent')->default(0);
            $table->date('completed_on')->nullable();
            $table->timestamps();
        });

        Schema::create('teaching_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('title');
            $table->string('resource_type')->default('notes'); // notes, book, pdf, video, image, presentation, worksheet
            $table->string('topic')->nullable();
            $table->string('term')->nullable();
            $table->string('file_url')->nullable();
            $table->text('description')->nullable();
            $table->boolean('shared')->default(false);
            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status')->default('submitted'); // submitted, graded, returned, resubmit
            $table->string('file_url')->nullable();
            $table->text('content')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('online_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('title');
            $table->string('lesson_type')->default('live'); // live, recorded, discussion, quiz, poll
            $table->timestamp('scheduled_at')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });

        Schema::create('report_card_narratives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->text('academic_comment')->nullable();
            $table->text('behaviour_comment')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->string('status')->default('draft'); // draft, submitted
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['teacher_id', 'student_id', 'term_id'], 'report_card_narratives_unique');
        });

        Schema::create('timetable_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('request_type')->default('change'); // change, conflict
            $table->text('details');
            $table->string('preferred_slot')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('class_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('absent_teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('substitute_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $table->date('date');
            $table->string('period')->nullable();
            $table->string('status')->default('open'); // open, accepted, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('class_participation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->date('recorded_on');
            $table->unsignedTinyInteger('score')->default(3); // 1-5
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('info');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        if (Schema::hasTable('assignments') && ! Schema::hasColumn('assignments', 'submission_type')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->string('submission_type')->default('file')->after('attachment_url');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_notifications');
        Schema::dropIfExists('class_participation_records');
        Schema::dropIfExists('class_substitutions');
        Schema::dropIfExists('timetable_change_requests');
        Schema::dropIfExists('report_card_narratives');
        Schema::dropIfExists('online_lessons');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('teaching_resources');
        Schema::dropIfExists('syllabus_topics');
        Schema::dropIfExists('lesson_plans');
        Schema::dropIfExists('attendance_sessions');

        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'locked_by')) {
                $table->dropConstrainedForeignId('locked_by');
            }
            foreach (['locked_at', 'submitted_at'] as $col) {
                if (Schema::hasColumn('attendance', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'submission_type')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->dropColumn('submission_type');
            });
        }
    }
};
