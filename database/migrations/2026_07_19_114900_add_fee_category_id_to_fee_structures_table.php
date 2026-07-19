<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->foreignId('fee_category_id')
                ->nullable()
                ->after('class_id')
                ->constrained('fee_categories')
                ->nullOnDelete();
        });

        // Backfill: match existing category strings to fee_categories (create if missing).
        $structures = DB::table('fee_structures')
            ->select(['id', 'school_id', 'category'])
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->get();

        foreach ($structures as $structure) {
            if (! $structure->school_id) {
                continue;
            }

            $categoryId = DB::table('fee_categories')
                ->where('school_id', $structure->school_id)
                ->where('name', $structure->category)
                ->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('fee_categories')->insertGetId([
                    'school_id' => $structure->school_id,
                    'name' => $structure->category,
                    'description' => 'Auto-created from existing fee structure',
                    'is_active' => true,
                    'order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('fee_structures')
                ->where('id', $structure->id)
                ->update(['fee_category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_category_id');
        });
    }
};
