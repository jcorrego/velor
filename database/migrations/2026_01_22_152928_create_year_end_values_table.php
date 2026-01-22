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
        Schema::create('year_end_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities');
            $table->foreignId('tax_year_id')->constrained('tax_years');
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('amount', 20, 2);
            $table->date('as_of_date');
            $table->timestamps();

            $table->unique(['entity_id', 'tax_year_id', 'account_id']);
            $table->unique(['entity_id', 'tax_year_id', 'asset_id']);
            $table->index(['entity_id', 'tax_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('year_end_values');
    }
};
