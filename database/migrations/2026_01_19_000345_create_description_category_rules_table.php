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
        Schema::create('description_category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurisdiction_id')->constrained('jurisdictions');
            $table->foreignId('category_id')->constrained('transaction_categories');
            $table->string('description_pattern'); // Text to match at start of description (case-insensitive)
            $table->string('counterparty')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['jurisdiction_id', 'description_pattern'], 'unique_jurisdiction_pattern');
            $table->index(['jurisdiction_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('description_category_rules');
    }
};
