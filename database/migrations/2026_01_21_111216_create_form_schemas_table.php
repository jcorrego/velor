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
        Schema::create('form_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('form_code');
            $table->unsignedInteger('tax_year');
            $table->string('title');
            $table->json('sections');
            $table->timestamps();

            $table->unique(['form_code', 'tax_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_schemas');
    }
};
