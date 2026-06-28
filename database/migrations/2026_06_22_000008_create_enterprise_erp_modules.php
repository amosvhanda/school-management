<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->string('academic_year');
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->text('syllabus')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'subject_id', 'academic_year', 'version_number']);
        });

        Schema::create('learning_outcome_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('outcome_code');
            $table->string('competency_level');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('evidence')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessed_on');
            $table->timestamps();
            $table->index(['student_id', 'subject_id']);
        });

        Schema::create('assessment_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('weight', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('continuous_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('assessment_categories')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->string('title');
            $table->decimal('score', 8, 2);
            $table->decimal('max_score', 8, 2)->default(100);
            $table->date('assessed_on');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('gradebook_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
            $table->json('category_weights');
            $table->decimal('pass_mark', 5, 2)->default(50);
            $table->timestamps();
        });

        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('prerequisite_subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->decimal('minimum_grade', 5, 2)->default(50);
            $table->timestamps();
            $table->unique(['subject_id', 'prerequisite_subject_id']);
        });

        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->json('conditions');
            $table->string('action');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academic_calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->string('entry_type');
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('account_type');
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('normal_balance')->default('debit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->date('entry_date');
            $table->text('description');
            $table->string('status')->default('posted');
            $table->string('currency', 3)->default('USD');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('memo')->nullable();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 18, 8);
            $table->date('effective_date');
            $table->timestamps();
            $table->unique(['school_id', 'from_currency', 'to_currency', 'effective_date']);
        });

        Schema::create('instalment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('instalment_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('instalment_plans')->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->foreignId('matched_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('match_status')->default('unmatched');
            $table->timestamps();
        });

        Schema::create('revenue_recognition_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('recognition_method');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('workflow_definition_steps') && ! Schema::hasColumn('workflow_definition_steps', 'approval_mode')) {
            Schema::table('workflow_definition_steps', function (Blueprint $table) {
                $table->string('approval_mode')->default('sequential')->after('position');
                $table->unsignedTinyInteger('required_approvals')->default(1)->after('approval_mode');
            });
        }

        Schema::create('workflow_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('delegator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delegate_id')->constrained('users')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('scope')->default('all_workflows');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('workflow_approvals') && ! Schema::hasColumn('workflow_approvals', 'attachments')) {
            Schema::table('workflow_approvals', function (Blueprint $table) {
                $table->json('attachments')->nullable()->after('comments');
                $table->string('rejection_reason')->nullable()->after('attachments');
            });
        }

        Schema::create('staff_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('contract_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamp('renewal_due_at')->nullable();
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('period');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->json('criteria_scores')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('staff_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('certification_name');
            $table->string('issuer')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->text('description');
            $table->text('outcome')->nullable();
            $table->string('status')->default('open');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('staff_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('shift_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('enrollment_application_id')->nullable()->constrained('enrollment_applications')->nullOnDelete();
            $table->string('applicant_name');
            $table->decimal('academic_score', 5, 2)->default(0);
            $table->decimal('interview_score', 5, 2)->default(0);
            $table->decimal('total_score', 5, 2)->default(0);
            $table->string('recommendation')->nullable();
            $table->timestamps();
        });

        Schema::create('alumni_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('full_name');
            $table->string('graduation_year');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('current_occupation')->nullable();
            $table->json('engagement_history')->nullable();
            $table->timestamps();
        });

        Schema::create('student_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['student_id', 'occurred_at']);
        });

        Schema::create('question_bank_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->text('question_text');
            $table->string('question_type')->default('multiple_choice');
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->string('difficulty')->default('medium');
            $table->json('tags')->nullable();
            $table->unsignedSmallInteger('marks')->default(1);
            $table->timestamps();
        });

        Schema::create('cbt_exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->json('question_ids');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('cbt_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('cbt_exam_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('question_bank_items')->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_anti_cheat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('cbt_exam_sessions')->cascadeOnDelete();
            $table->string('event_type');
            $table->json('metadata')->nullable();
            $table->timestamp('logged_at')->useCurrent();
        });

        Schema::create('remark_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_result_id')->constrained('exam_results')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->json('channels');
            $table->json('audience_filter');
            $table->text('message_body');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('communication_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('channel_preferences');
            $table->json('topic_preferences')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('parent_engagement_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('title');
            $table->timestamp('attended_at')->nullable();
            $table->boolean('attended')->default(false);
            $table->timestamps();
        });

        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('asset_type');
            $table->foreignId('asset_id')->nullable();
            $table->string('title');
            $table->string('frequency');
            $table->date('next_due_date');
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });

        Schema::create('facility_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('task_type');
            $table->string('location');
            $table->date('scheduled_date');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('room_utilization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->date('log_date');
            $table->unsignedSmallInteger('hours_used')->default(0);
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->string('stop_name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('stop_order')->default(1);
            $table->time('pickup_time')->nullable();
        });

        Schema::create('gps_tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2)->nullable();
            $table->timestamp('recorded_at');
        });

        Schema::create('fuel_consumption_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('litres', 8, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->date('trip_date');
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });

        Schema::create('library_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('queued');
            $table->timestamps();
        });

        Schema::create('digital_library_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->string('resource_type');
            $table->string('file_path')->nullable();
            $table->string('isbn')->nullable();
            $table->json('access_roles')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
        });

        Schema::create('library_fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained('library_loans')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('compliance_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('authority');
            $table->string('requirement_code');
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->json('evidence')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_connectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('provider');
            $table->string('connector_type');
            $table->json('config');
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'provider', 'connector_type']);
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->string('secret_hash');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('abac_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('resource');
            $table->json('conditions');
            $table->string('effect')->default('allow');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('group_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('policy_code');
            $table->string('name');
            $table->json('rules');
            $table->boolean('enforce_on_branches')->default(true);
            $table->timestamps();
            $table->unique(['parent_school_id', 'policy_code']);
        });

        Schema::create('cross_school_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('to_school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shared_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->decimal('allocation_percent', 5, 2)->default(100);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_teacher_assignments');
        Schema::dropIfExists('cross_school_transfers');
        Schema::dropIfExists('group_policies');
        Schema::dropIfExists('device_sessions');
        Schema::dropIfExists('abac_policies');
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('integration_connectors');
        Schema::dropIfExists('compliance_requirements');
        Schema::dropIfExists('library_fines');
        Schema::dropIfExists('digital_library_resources');
        Schema::dropIfExists('library_reservations');
        Schema::dropIfExists('transport_trips');
        Schema::dropIfExists('fuel_consumption_logs');
        Schema::dropIfExists('gps_tracking_logs');
        Schema::dropIfExists('transport_route_stops');
        Schema::dropIfExists('room_utilization_logs');
        Schema::dropIfExists('facility_tasks');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('parent_engagement_events');
        Schema::dropIfExists('communication_preferences');
        Schema::dropIfExists('message_campaigns');
        Schema::dropIfExists('remark_requests');
        Schema::dropIfExists('exam_anti_cheat_logs');
        Schema::dropIfExists('cbt_responses');
        Schema::dropIfExists('cbt_exam_sessions');
        Schema::dropIfExists('question_bank_items');
        Schema::dropIfExists('student_timeline_events');
        Schema::dropIfExists('alumni_records');
        Schema::dropIfExists('admission_scores');
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('staff_disciplinary_cases');
        Schema::dropIfExists('staff_certifications');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('staff_contracts');
        Schema::dropIfExists('workflow_delegations');
        Schema::dropIfExists('revenue_recognition_rules');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('instalment_schedule_items');
        Schema::dropIfExists('instalment_plans');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('academic_calendar_entries');
        Schema::dropIfExists('promotion_rules');
        Schema::dropIfExists('subject_prerequisites');
        Schema::dropIfExists('gradebook_rules');
        Schema::dropIfExists('continuous_assessments');
        Schema::dropIfExists('assessment_categories');
        Schema::dropIfExists('learning_outcome_records');
        Schema::dropIfExists('curriculum_versions');

        if (Schema::hasTable('workflow_approvals')) {
            Schema::table('workflow_approvals', function (Blueprint $table) {
                foreach (['rejection_reason', 'attachments'] as $col) {
                    if (Schema::hasColumn('workflow_approvals', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('workflow_definition_steps') && Schema::hasColumn('workflow_definition_steps', 'approval_mode')) {
            Schema::table('workflow_definition_steps', function (Blueprint $table) {
                $table->dropColumn(['approval_mode', 'required_approvals']);
            });
        }
    }
};
