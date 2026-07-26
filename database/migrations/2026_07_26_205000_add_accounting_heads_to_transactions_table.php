<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('income_head_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('income_heads')
                ->nullOnDelete();
            $table->foreignId('expense_head_id')
                ->nullable()
                ->after('income_head_id')
                ->constrained('expense_heads')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('income_head_id');
            $table->dropConstrainedForeignId('expense_head_id');
        });
    }
};
