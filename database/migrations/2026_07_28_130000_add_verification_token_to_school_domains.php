<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_domains', function (Blueprint $table) {
            $table->string('verification_token', 64)->nullable()->after('domain');
        });
    }

    public function down(): void
    {
        Schema::table('school_domains', function (Blueprint $table) {
            $table->dropColumn('verification_token');
        });
    }
};
