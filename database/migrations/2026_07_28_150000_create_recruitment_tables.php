<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('employment_type')->default('full_time');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedSmallInteger('openings')->default(1);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->string('applicant_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('resume_url')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('status')->default('submitted');
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['job_posting_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
    }
};
