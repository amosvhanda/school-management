<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->string('first_name');
            $table->string('surname');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('national_id')->nullable();
            $table->text('address');
            $table->string('suburb')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('grade_applying_for');
            $table->string('academic_year');
            $table->string('guardian_first_name');
            $table->string('guardian_surname');
            $table->string('guardian_relationship');
            $table->string('guardian_phone');
            $table->string('guardian_email')->nullable();
            $table->text('guardian_address');
            $table->string('guardian_employer')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->string('emergency_contact');
            $table->string('emergency_phone');
            $table->boolean('birth_certificate')->default(false);
            $table->boolean('report_cards')->default(false);
            $table->boolean('medical_certificate')->default(false);
            $table->boolean('passport_photo')->default(false);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index('school_id');
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_applications');
    }
};
