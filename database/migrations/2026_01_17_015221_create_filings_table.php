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
        Schema::create('filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('filing_type_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // Will use enum
            $table->date('due_date')->nullable();
            $table->json('form_data')->nullable();
            $table->json('key_metrics')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tax_year_id', 'filing_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filings');
    }
};
