<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schools') && ! Schema::hasColumn('schools', 'parent_school_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->foreignId('parent_school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
                $table->string('branch_type')->nullable()->after('parent_school_id');
                $table->index('parent_school_id');
            });
        }

        Schema::create('policy_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('module');
            $table->string('trigger_event');
            $table->json('conditions');
            $table->json('actions');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        if (Schema::hasTable('workflow_definitions') && ! Schema::hasColumn('workflow_definitions', 'layout')) {
            Schema::table('workflow_definitions', function (Blueprint $table) {
                $table->json('layout')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('workflow_definition_steps') && ! Schema::hasColumn('workflow_definition_steps', 'escalation_hours')) {
            Schema::table('workflow_definition_steps', function (Blueprint $table) {
                $table->unsignedSmallInteger('escalation_hours')->nullable()->after('approver_user_id');
                $table->json('position')->nullable()->after('escalation_hours');
            });
        }

        if (Schema::hasTable('workflow_instances') && ! Schema::hasColumn('workflow_instances', 'step_due_at')) {
            Schema::table('workflow_instances', function (Blueprint $table) {
                $table->timestamp('step_due_at')->nullable()->after('current_step_order');
                $table->timestamp('escalated_at')->nullable()->after('step_due_at');
                $table->unsignedTinyInteger('escalation_count')->default(0)->after('escalated_at');
            });
        }

        Schema::create('hub_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('channels');
            $table->string('audience_type')->default('individual');
            $table->json('audience_ids')->nullable();
            $table->string('status')->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->json('delivery_stats')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status']);
        });

        Schema::create('hub_message_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_message_id')->constrained('hub_messages')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel');
            $table->string('recipient_address')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['hub_message_id', 'channel']);
            $table->index(['recipient_user_id', 'read_at']);
        });

        if (Schema::hasTable('communication_messages') && ! Schema::hasColumn('communication_messages', 'read_by_recipient_at')) {
            Schema::table('communication_messages', function (Blueprint $table) {
                $table->timestamp('read_by_recipient_at')->nullable()->after('read_at');
            });
        }

        Schema::create('signable_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('signable_documents')->cascadeOnDelete();
            $table->foreignId('signer_id')->constrained('users')->cascadeOnDelete();
            $table->string('signer_role')->nullable();
            $table->string('signature_hash');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
            $table->unique(['document_id', 'signer_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('certificate_type');
            $table->string('title');
            $table->string('verification_code')->unique();
            $table->text('content_html');
            $table->json('metadata')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'certificate_type']);
        });

        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->text('criteria')->nullable();
            $table->date('application_deadline')->nullable();
            $table->unsignedSmallInteger('slots')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_id')->constrained('scholarships')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->text('motivation')->nullable();
            $table->json('supporting_data')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['scholarship_id', 'student_id']);
        });

        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('provider');
            $table->json('credentials');
            $table->boolean('is_active')->default(false);
            $table->boolean('supports_cards')->default(true);
            $table->boolean('supports_mobile_money')->default(false);
            $table->boolean('supports_bank_transfer')->default(false);
            $table->timestamps();
            $table->unique(['school_id', 'provider']);
        });

        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('config_id')->constrained('payment_gateway_configs')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('provider_reference')->nullable();
            $table->string('internal_reference')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method');
            $table->string('status')->default('initiated');
            $table->json('provider_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status']);
        });

        Schema::create('fee_penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->string('penalty_type');
            $table->decimal('penalty_value', 12, 4);
            $table->string('frequency')->default('once');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fee_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('rule_id')->constrained('fee_penalty_rules')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('applied_on');
            $table->string('status')->default('applied');
            $table->timestamps();
            $table->index(['invoice_id', 'applied_on']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('secure_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('vault_type')->default('exam_paper');
            $table->string('title');
            $table->text('encrypted_payload');
            $table->string('content_hash');
            $table->json('access_roles');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'vault_type']);
        });

        Schema::create('secure_document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('secure_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('accessed_at')->useCurrent();
        });

        if (Schema::hasTable('exam_results') && ! Schema::hasColumn('exam_results', 'is_locked')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('approved_at');
                $table->timestamp('locked_at')->nullable()->after('is_locked');
            });
        }

        Schema::create('behavior_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->integer('points');
            $table->string('category');
            $table->text('description');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->date('recorded_on');
            $table->timestamps();
            $table->index(['student_id', 'recorded_on']);
        });

        Schema::create('student_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('intervention_type');
            $table->string('status')->default('open');
            $table->text('summary');
            $table->text('action_plan')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('follow_up_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority')->default('normal');
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('staff_feed_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->string('visibility')->default('staff');
            $table->timestamps();
        });

        Schema::create('staff_feed_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('staff_feed_posts')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('operations_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('severity');
            $table->string('category');
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'resolved_at']);
        });

        Schema::create('retention_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('module');
            $table->unsignedSmallInteger('retain_years');
            $table->string('archive_action')->default('archive');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'module']);
        });

        Schema::create('archived_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('source_table');
            $table->unsignedBigInteger('source_id');
            $table->json('snapshot');
            $table->timestamp('archived_at')->useCurrent();
            $table->index(['school_id', 'source_table']);
        });

        Schema::create('data_masking_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('module');
            $table->string('field');
            $table->json('visible_roles');
            $table->string('mask_pattern')->default('***');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'module', 'field']);
        });

        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('client_id')->unique();
            $table->string('client_secret_hash');
            $table->json('scopes');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_logs', 'integrity_hash')) {
                    $table->string('integrity_hash', 64)->nullable()->after('created_at');
                }
                if (! Schema::hasColumn('audit_logs', 'previous_hash')) {
                    $table->string('previous_hash', 64)->nullable()->after('integrity_hash');
                }
            });
        }

        Schema::create('record_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('versionable_type');
            $table->unsignedBigInteger('versionable_id');
            $table->unsignedInteger('version_number');
            $table->json('snapshot');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['versionable_type', 'versionable_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_versions');

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                foreach (['previous_hash', 'integrity_hash'] as $col) {
                    if (Schema::hasColumn('audit_logs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('api_clients');
        Schema::dropIfExists('data_masking_rules');
        Schema::dropIfExists('archived_records');
        Schema::dropIfExists('retention_policies');
        Schema::dropIfExists('operations_alerts');
        Schema::dropIfExists('staff_feed_comments');
        Schema::dropIfExists('staff_feed_posts');
        Schema::dropIfExists('staff_tasks');
        Schema::dropIfExists('student_interventions');
        Schema::dropIfExists('behavior_points');
        Schema::dropIfExists('secure_document_access_logs');
        Schema::dropIfExists('secure_documents');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('fee_penalties');
        Schema::dropIfExists('fee_penalty_rules');
        Schema::dropIfExists('payment_gateway_transactions');
        Schema::dropIfExists('payment_gateway_configs');
        Schema::dropIfExists('scholarship_applications');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('document_signatures');
        Schema::dropIfExists('signable_documents');
        Schema::dropIfExists('hub_message_deliveries');
        Schema::dropIfExists('hub_messages');
        Schema::dropIfExists('policy_rules');

        if (Schema::hasTable('exam_results') && Schema::hasColumn('exam_results', 'is_locked')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->dropColumn(['is_locked', 'locked_at']);
            });
        }

        if (Schema::hasTable('workflow_instances')) {
            Schema::table('workflow_instances', function (Blueprint $table) {
                foreach (['escalation_count', 'escalated_at', 'step_due_at'] as $col) {
                    if (Schema::hasColumn('workflow_instances', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('workflow_definition_steps') && Schema::hasColumn('workflow_definition_steps', 'escalation_hours')) {
            Schema::table('workflow_definition_steps', function (Blueprint $table) {
                $table->dropColumn(['escalation_hours', 'position']);
            });
        }

        if (Schema::hasTable('workflow_definitions') && Schema::hasColumn('workflow_definitions', 'layout')) {
            Schema::table('workflow_definitions', function (Blueprint $table) {
                $table->dropColumn('layout');
            });
        }

        if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'parent_school_id')) {
            Schema::table('schools', function (Blueprint $table) {
                // SQLite requires the index to be dropped before the column.
                $table->dropIndex(['parent_school_id']);
            });

            Schema::table('schools', function (Blueprint $table) {
                $table->dropForeign(['parent_school_id']);
            });

            Schema::table('schools', function (Blueprint $table) {
                $columns = collect(['parent_school_id', 'branch_type'])
                    ->filter(fn (string $column) => Schema::hasColumn('schools', $column))
                    ->values()
                    ->all();

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
