<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Consolidate duplicate categories across jurisdictions
        // Get all categories grouped by name
        $categories = DB::table('transaction_categories')
            ->select('name', DB::raw('MIN(id) as keep_id'), DB::raw('GROUP_CONCAT(id) as all_ids'))
            ->groupBy('name')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        foreach ($categories as $categoryGroup) {
            $idsToMerge = explode(',', $categoryGroup->all_ids);
            $keepId = $categoryGroup->keep_id;
            $mergeIds = array_filter($idsToMerge, fn ($id) => $id != $keepId);

            if (! empty($mergeIds)) {
                // Update category_tax_mappings to point to the kept category
                DB::table('category_tax_mappings')
                    ->whereIn('category_id', $mergeIds)
                    ->update(['category_id' => $keepId]);

                // Update transactions to point to the kept category
                DB::table('transactions')
                    ->whereIn('category_id', $mergeIds)
                    ->update(['category_id' => $keepId]);

                // Delete the duplicate categories
                DB::table('transaction_categories')
                    ->whereIn('id', $mergeIds)
                    ->delete();
            }
        }

        // Step 2: Drop the jurisdiction_id foreign key first
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropForeign(['jurisdiction_id']);
        });

        // Step 3: Drop the unique constraint on [jurisdiction_id, name]
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropUnique(['jurisdiction_id', 'name']);
        });

        // Step 4: Drop the jurisdiction_id column
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropColumn('jurisdiction_id');
        });

        // Step 5: Add unique constraint on name only
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop unique constraint on name
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        // Step 2: Add back jurisdiction_id column as nullable first
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->foreignId('jurisdiction_id')->after('name')->nullable()->constrained('jurisdictions');
        });

        // Step 3: Add back unique constraint on [jurisdiction_id, name]
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->unique(['jurisdiction_id', 'name']);
        });

        // Note: We cannot automatically reverse the category consolidation
        // Manual intervention would be required to restore jurisdiction-specific categories
        // and populate jurisdiction_id values
    }
};
