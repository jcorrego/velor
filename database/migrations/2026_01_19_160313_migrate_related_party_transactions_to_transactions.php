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
        Schema::dropIfExists('related_party_transactions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('related_party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->decimal('amount', 20, 2);
            $table->string('type');
            $table->foreignId('owner_id')->constrained('users');
            $table->foreignId('account_id')->constrained('accounts');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('transaction_date');
        });
    }
};
