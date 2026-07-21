<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('principal_name')->nullable()->after('contact_email');
            $table->string('website')->nullable()->after('principal_name');
            $table->year('year_founded')->nullable()->after('website');
            $table->string('suburb')->nullable()->after('year_founded');
            $table->string('city')->nullable()->after('suburb');
            $table->unsignedInteger('student_capacity')->nullable()->after('city');
            $table->string('timezone')->nullable()->after('student_capacity');
            $table->string('motto')->nullable()->after('timezone');
            $table->string('logo_path')->nullable()->after('motto');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'principal_name',
                'website',
                'year_founded',
                'suburb',
                'city',
                'student_capacity',
                'timezone',
                'motto',
                'logo_path',
            ]);
        });
    }
};
