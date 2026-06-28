<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add foreign key constraint for users.school_id after schools table is created.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasTable('schools') && Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
            });
        }
    }
};
