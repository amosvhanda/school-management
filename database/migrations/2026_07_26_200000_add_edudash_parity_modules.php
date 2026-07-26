<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });

        Schema::create('fee_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_group_id')->constrained('fee_groups')->cascadeOnDelete();
            $table->foreignId('fee_category_id')->constrained('fee_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['fee_group_id', 'fee_category_id']);
        });

        Schema::create('student_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });

        Schema::create('fee_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type'); // percent | fixed
            $table->decimal('value', 12, 2);
            $table->foreignId('fee_category_id')->nullable()->constrained('fee_categories')->nullOnDelete();
            $table->foreignId('student_category_id')->nullable()->constrained('student_categories')->nullOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('employee_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('joining_date')->nullable();
            $table->string('employment_type')->default('full_time');
            $table->string('status')->default('active');
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->unique(['school_id', 'employee_number']);
        });

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->date('date');
            $table->string('staff_type'); // teacher | employee
            $table->unsignedBigInteger('staff_id');
            $table->string('status'); // present | absent | late | half_day | on_leave
            $table->string('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'date', 'staff_type', 'staff_id'], 'staff_attendance_unique');
            $table->index(['school_id', 'date']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('default_days')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });

        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('certificate_type');
            $table->string('title');
            $table->longText('body_html');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });

        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('invigilator')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'exam_id']);
            $table->index(['school_id', 'starts_at']);
        });

        Schema::create('income_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });

        Schema::create('expense_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index('school_id');
        });

        Schema::create('library_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('member_type'); // student | teacher | employee | external
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('member_number')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->date('joined_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->unique(['school_id', 'member_number']);
        });

        Schema::create('school_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('code', 3);
            $table->string('name');
            $table->string('symbol', 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'code']);
            $table->index('school_id');
        });

        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'student_category_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('student_category_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('student_categories')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('leave_requests') && ! Schema::hasColumn('leave_requests', 'leave_type_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignId('leave_type_id')
                    ->nullable()
                    ->after('teacher_id')
                    ->constrained('leave_types')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('teachers') && ! Schema::hasColumn('teachers', 'designation_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->foreignId('designation_id')
                    ->nullable()
                    ->after('department')
                    ->constrained('designations')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('certificates') && ! Schema::hasColumn('certificates', 'certificate_template_id')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->foreignId('certificate_template_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('certificate_templates')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('certificates') && Schema::hasColumn('certificates', 'certificate_template_id')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->dropConstrainedForeignId('certificate_template_id');
            });
        }

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'designation_id')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('designation_id');
            });
        }

        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'leave_type_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('leave_type_id');
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'student_category_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('student_category_id');
            });
        }

        Schema::dropIfExists('school_currencies');
        Schema::dropIfExists('library_members');
        Schema::dropIfExists('expense_heads');
        Schema::dropIfExists('income_heads');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('fee_discounts');
        Schema::dropIfExists('fee_group_items');
        Schema::dropIfExists('fee_groups');
        Schema::dropIfExists('student_categories');
    }
};
