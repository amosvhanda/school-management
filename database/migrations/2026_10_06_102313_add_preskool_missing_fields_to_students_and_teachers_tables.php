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
        Schema::table('students', function (Blueprint $table) {
            $table->date('admission_date')->nullable()->after('date_of_birth');
            $table->string('religion')->nullable()->after('gender');
            $table->string('caste')->nullable()->after('religion');
            $table->string('father_name')->nullable()->after('guardian_last_name');
            $table->string('father_phone')->nullable()->after('father_name');
            $table->string('father_occupation')->nullable()->after('father_phone');
            $table->string('mother_name')->nullable()->after('father_occupation');
            $table->string('mother_phone')->nullable()->after('mother_name');
            $table->string('mother_occupation')->nullable()->after('mother_phone');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('national_id')->nullable()->after('date_of_birth');
            $table->string('work_experience')->nullable()->after('qualification');
            $table->string('previous_school')->nullable()->after('work_experience');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'admission_date',
                'religion',
                'caste',
                'father_name',
                'father_phone',
                'father_occupation',
                'mother_name',
                'mother_phone',
                'mother_occupation',
            ]);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'national_id',
                'work_experience',
                'previous_school',
            ]);
        });
    }
};
