<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_subscriptions', function (Blueprint $table) {
            $table->text('secret')->nullable()->after('target_url');
            $table->string('secret_hash')->nullable()->change();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');

        Schema::table('webhook_subscriptions', function (Blueprint $table) {
            $table->dropColumn('secret');
            $table->string('secret_hash')->nullable(false)->change();
        });
    }
};
