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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jurisdiction_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('tax_id'); // Encrypted via cast
            $table->string('default_currency', 3)->nullable();
            $table->json('display_currencies')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'jurisdiction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
