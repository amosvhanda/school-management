<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('module');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        Schema::create('workflow_definition_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('name');
            $table->string('approver_role')->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['definition_id', 'step_order']);
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('current_step_order')->default(1);
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->useCurrent();
            $table->index(['instance_id', 'step_order']);
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->string('status')->default('received');
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('asset_tag')->nullable();
            $table->string('name');
            $table->string('category');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->string('location')->nullable();
            $table->foreignId('custodian_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['school_id', 'category']);
        });

        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->text('description');
            $table->decimal('cost', 12, 2)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('disposed_at');
            $table->text('reason');
            $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('registration_number');
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('license_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->text('route_description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('student_transport_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->string('pickup_point')->nullable();
            $table->string('status')->default('active');
            $table->date('allocated_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'route_id']);
        });

        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('gender')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->string('room_number');
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->timestamps();
            $table->unique(['hostel_id', 'room_number']);
        });

        Schema::create('hostel_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('hostel_rooms')->cascadeOnDelete();
            $table->string('bed_number');
            $table->string('status')->default('available');
            $table->timestamps();
            $table->unique(['room_id', 'bed_number']);
        });

        Schema::create('hostel_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained('hostel_beds')->cascadeOnDelete();
            $table->date('allocated_at');
            $table->date('left_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->string('author')->nullable();
            $table->unsignedInteger('total_copies')->default(1);
            $table->unsignedInteger('available_copies')->default(1);
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('borrowed_at');
            $table->date('due_at');
            $table->date('returned_at')->nullable();
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->string('status')->default('borrowed');
            $table->timestamps();
        });

        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('id_number')->nullable();
            $table->string('purpose');
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('status')->default('checked_in');
            $table->timestamps();
        });

        Schema::create('student_medical_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->text('conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->string('blood_group')->nullable();
            $table->text('emergency_notes')->nullable();
            $table->text('medications')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('visit_date');
            $table->text('complaint');
            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->foreignId('nurse_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('school_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('general');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->longText('content');
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->string('severity')->default('low');
            $table->text('description');
            $table->string('status')->default('open');
            $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consent_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('target_audience')->default('parents');
            $table->date('due_date')->nullable();
            $table->boolean('requires_signature')->default(false);
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consent_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('consent_forms')->cascadeOnDelete();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['form_id', 'parent_user_id', 'student_id']);
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('fiscal_year');
            $table->string('department')->nullable();
            $table->decimal('allocated_amount', 14, 2);
            $table->decimal('spent_amount', 14, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('consent_responses');
        Schema::dropIfExists('consent_forms');
        Schema::dropIfExists('incident_reports');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('school_events');
        Schema::dropIfExists('clinic_visits');
        Schema::dropIfExists('student_medical_profiles');
        Schema::dropIfExists('visitors');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('hostel_beds');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
        Schema::dropIfExists('student_transport_allocations');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_maintenance_logs');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definition_steps');
        Schema::dropIfExists('workflow_definitions');
    }
};
