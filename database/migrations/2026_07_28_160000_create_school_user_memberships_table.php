<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_user_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'user_id']);
            $table->index(['user_id', 'is_default']);
        });

        // Backfill current single-school assignments as memberships.
        DB::table('users')
            ->whereNotNull('school_id')
            ->orderBy('id')
            ->chunkById(500, function ($users) {
                $now = now();
                $rows = [];
                foreach ($users as $user) {
                    $rows[] = [
                        'school_id' => $user->school_id,
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'is_default' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows !== []) {
                    DB::table('school_user_memberships')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_user_memberships');
    }
};
