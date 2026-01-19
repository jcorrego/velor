<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the jurisdiction_id foreign key
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropForeign(['jurisdiction_id']);
        });

        // Drop the unique constraint on [jurisdiction_id, name]
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropUnique(['jurisdiction_id', 'name']);
        });

        // Drop the jurisdiction_id column
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropColumn('jurisdiction_id');
        });

        // Add unique constraint on name only
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop unique constraint on name
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        // Add back jurisdiction_id column
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->foreignId('jurisdiction_id')->after('name')->nullable()->constrained('jurisdictions');
        });

        // Add back unique constraint on [jurisdiction_id, name]
        Schema::table('transaction_categories', function (Blueprint $table) {
            $table->unique(['jurisdiction_id', 'name']);
        });
    }
};
