<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['school_id', 'date', 'status'], 'payments_school_date_status_idx');
            $table->index(['school_id', 'currency', 'status'], 'payments_school_currency_status_idx');
            $table->index(['school_id', 'student_id', 'date'], 'payments_school_student_date_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['school_id', 'status', 'due_date'], 'invoices_school_status_due_idx');
            $table->index(['school_id', 'student_id', 'status'], 'invoices_school_student_status_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['school_id', 'created_at'], 'transactions_school_created_idx');
            $table->index(['school_id', 'student_id', 'created_at'], 'transactions_school_student_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_school_date_status_idx');
            $table->dropIndex('payments_school_currency_status_idx');
            $table->dropIndex('payments_school_student_date_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_school_status_due_idx');
            $table->dropIndex('invoices_school_student_status_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_school_created_idx');
            $table->dropIndex('transactions_school_student_created_idx');
        });
    }
};
