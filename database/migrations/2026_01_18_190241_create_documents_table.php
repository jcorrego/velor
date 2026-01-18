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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jurisdiction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('storage_disk');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_legal')->default(false);
            $table->longText('extracted_text')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('jurisdiction_id');
            $table->index('tax_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
