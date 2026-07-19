<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('platform_terms_version')->nullable()->after('status');
            $table->timestamp('platform_terms_accepted_at')->nullable()->after('platform_terms_version');
        });

        $version = (string) config('platform_terms.version');
        $acceptedAt = now();

        DB::table('users')->update([
            'platform_terms_version' => $version,
            'platform_terms_accepted_at' => $acceptedAt,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['platform_terms_version', 'platform_terms_accepted_at']);
        });
    }
};
