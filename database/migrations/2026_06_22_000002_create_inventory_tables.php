<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_items')) {
            return;
        }

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('type')->default('uniform');
            $table->string('size')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(5);
            $table->string('billing_mode')->default('direct_sale');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'type']);
            $table->unique(['school_id', 'sku']);
        });

        Schema::create('inventory_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('sale_number');
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->default('student_account');
            $table->string('status')->default('completed');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'sale_number']);
        });

        Schema::create('inventory_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_sale_id')->constrained('inventory_sales')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type');
            $table->integer('quantity_change');
            $table->unsignedInteger('quantity_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_sale_items');
        Schema::dropIfExists('inventory_sales');
        Schema::dropIfExists('inventory_items');
    }
};
