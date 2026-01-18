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
        if (Schema::hasTable('documentables')) {
            Schema::table('documentables', function (Blueprint $table) {
                $table->unique(['document_id', 'documentable_id', 'documentable_type'], 'documentables_docable_unique');
            });

            return;
        }

        Schema::create('documentables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->morphs('documentable');
            $table->timestamps();

            $table->unique(['document_id', 'documentable_id', 'documentable_type'], 'documentables_docable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentables');
    }
};
