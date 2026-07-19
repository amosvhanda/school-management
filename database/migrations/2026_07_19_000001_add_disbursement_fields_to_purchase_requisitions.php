<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->string('spend_type', 40)->default('procurement')->after('description');
            $table->foreignId('vendor_id')->nullable()->after('department_id')->constrained('vendors')->nullOnDelete();
            $table->decimal('amount_paid', 12, 2)->nullable()->after('estimated_cost');
            $table->string('payment_method')->nullable()->after('amount_paid');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->timestamp('disbursed_at')->nullable()->after('payment_reference');
            $table->foreignId('disbursed_by')->nullable()->after('disbursed_at')->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->after('disbursed_by')->constrained('transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropConstrainedForeignId('disbursed_by');
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn([
                'spend_type',
                'amount_paid',
                'payment_method',
                'payment_reference',
                'disbursed_at',
            ]);
        });
    }
};
