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
        Schema::create('jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 3)->unique();
            $table->string('timezone');
            $table->string('default_currency', 3);
            $table->tinyInteger('tax_year_start_month')->default(1);
            $table->tinyInteger('tax_year_start_day')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurisdictions');
    }
};
