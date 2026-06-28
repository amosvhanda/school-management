<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'license_status')) {
                $table->string('license_status', 20)->default('none')->after('status');
            }
            if (! Schema::hasColumn('schools', 'license_plan')) {
                $table->string('license_plan', 20)->nullable()->after('license_status');
            }
            if (! Schema::hasColumn('schools', 'license_expires_at')) {
                $table->timestamp('license_expires_at')->nullable()->after('license_plan');
            }
        });

        if (Schema::hasTable('license_keys')) {
            return;
        }

        Schema::create('license_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key_prefix', 32)->index();
            $table->string('key_hash', 64)->unique();
            $table->string('plan_type', 20);
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->string('status', 20)->default('unused');
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_keys');

        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                $columns = collect(['license_status', 'license_plan', 'license_expires_at'])
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
