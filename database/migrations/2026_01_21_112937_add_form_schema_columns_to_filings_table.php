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
        Schema::table('filings', function (Blueprint $table) {
            $table->foreignId('form_schema_id')->nullable()->after('due_date')->constrained('form_schemas')->nullOnDelete();
            $table->json('form_data')->nullable()->after('form_schema_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filings', function (Blueprint $table) {
            $table->dropForeign(['form_schema_id']);
            $table->dropColumn(['form_schema_id', 'form_data']);
        });
    }
};
