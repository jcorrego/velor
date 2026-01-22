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
        Schema::dropIfExists('asset_valuations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('asset_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets');
            $table->decimal('amount', 20, 2);
            $table->date('valuation_date');
            $table->string('method');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['asset_id', 'valuation_date']);
        });
    }
};
