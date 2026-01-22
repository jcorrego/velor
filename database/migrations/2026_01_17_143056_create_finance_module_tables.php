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
        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // FX Rates
        Schema::create('fx_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_from_id')->constrained('currencies');
            $table->foreignId('currency_to_id')->constrained('currencies');
            $table->decimal('rate', 20, 8);
            $table->date('rate_date');
            $table->enum('source', ['ecb', 'manual', 'override']);
            $table->timestamps();
            $table->unique(['currency_from_id', 'currency_to_id', 'rate_date']);
        });

        // Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('entity_id')->constrained('entities');
            $table->date('opening_date');
            $table->date('closing_date')->nullable();
            $table->json('integration_metadata')->nullable();
            $table->timestamps();
            $table->unique(['entity_id', 'name', 'type']);
        });

        // Assets
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->foreignId('jurisdiction_id')->constrained('jurisdictions');
            $table->foreignId('entity_id')->constrained('entities');
            $table->string('ownership_structure');
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 20, 2);
            $table->timestamps();
        });

        // Transaction Categories
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('jurisdiction_id')->constrained('jurisdictions');
            $table->enum('income_or_expense', ['income', 'expense']);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['jurisdiction_id', 'name']);
        });

        // Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('type');
            $table->decimal('original_amount', 20, 2);
            $table->foreignId('original_currency_id')->constrained('currencies');
            $table->decimal('converted_amount', 20, 2);
            $table->foreignId('converted_currency_id')->constrained('currencies');
            $table->decimal('fx_rate', 20, 8);
            $table->string('fx_source');
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories');
            $table->string('counterparty_name')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->string('import_source')->nullable();
            $table->timestamps();
            $table->index('transaction_date');
            $table->index('account_id');
        });

        // Category Tax Mappings
        Schema::create('category_tax_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('transaction_categories');
            $table->string('tax_form_code');
            $table->string('line_item');
            $table->string('country');
            $table->timestamps();
            $table->unique(['category_id', 'tax_form_code', 'line_item']);
        });

        // Transaction Imports
        Schema::create('transaction_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('file_type');
            $table->string('file_name');
            $table->integer('parsed_count')->default(0);
            $table->integer('matched_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_imports');
        Schema::dropIfExists('category_tax_mappings');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('assets');
        // Drop import_mapping_profiles if it exists (from a missing migration)
        Schema::dropIfExists('import_mapping_profiles');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('fx_rates');
        Schema::dropIfExists('currencies');
    }
};
