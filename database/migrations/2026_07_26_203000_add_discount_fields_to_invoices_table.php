<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('original_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('original_amount');
            $table->foreignId('fee_discount_id')->nullable()->after('fee_structure_id')->constrained('fee_discounts')->nullOnDelete();
            $table->foreignId('fee_group_id')->nullable()->after('fee_discount_id')->constrained('fee_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_group_id');
            $table->dropConstrainedForeignId('fee_discount_id');
            $table->dropColumn(['original_amount', 'discount_amount']);
        });
    }
};
