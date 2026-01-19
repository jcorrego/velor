<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Migrate existing related_party_transactions to transactions table
        // Note: This migration only handles existing data. For new installations,
        // the Form 5472 categories and mappings are created by the seeder.
        if (! Schema::hasTable('related_party_transactions')) {
            return;
        }

        // Build a mapping of line_item to category_id for existing Form 5472 mappings
        $categoryMappings = [];
        $form5472Mappings = DB::table('category_tax_mappings')
            ->where('tax_form_code', 'form_5472')
            ->get();

        foreach ($form5472Mappings as $mapping) {
            $categoryMappings[$mapping->line_item] = $mapping->category_id;
        }

        // Step 2: Migrate related_party_transactions to transactions table
        $relatedPartyTransactions = DB::table('related_party_transactions')->get();

        foreach ($relatedPartyTransactions as $rpt) {
            // Get the account to determine currencies
            $account = DB::table('accounts')->find($rpt->account_id);
            if (! $account) {
                continue;
            }

            // Determine the category based on type
            $categoryId = $categoryMappings[$rpt->type] ?? null;
            if (! $categoryId) {
                continue;
            }

            // Insert as a transaction
            DB::table('transactions')->insert([
                'transaction_date' => $rpt->transaction_date,
                'account_id' => $rpt->account_id,
                'type' => str_contains($rpt->type, 'contribution') || str_contains($rpt->type, 'reimbursement') ? 'income' : 'expense',
                'original_amount' => $rpt->amount,
                'original_currency_id' => $account->currency_id,
                'converted_amount' => $rpt->amount, // Will be recalculated if needed
                'converted_currency_id' => $account->currency_id,
                'fx_rate' => 1.0,
                'fx_source' => 'manual',
                'category_id' => $categoryId,
                'counterparty_name' => null,
                'description' => $rpt->description,
                'tags' => json_encode(['migrated_from_related_party' => true, 'owner_id' => $rpt->owner_id]),
                'reconciled_at' => null,
                'import_source' => 'related_party_migration',
                'created_at' => $rpt->created_at,
                'updated_at' => $rpt->updated_at,
            ]);
        }

        // Step 3: Drop the related_party_transactions table
        Schema::dropIfExists('related_party_transactions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate related_party_transactions table
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

        // Migrate back transactions that were created from related-party transactions
        $migratedTransactions = DB::table('transactions')
            ->where('import_source', 'related_party_migration')
            ->get();

        foreach ($migratedTransactions as $transaction) {
            $tags = json_decode($transaction->tags, true);
            $ownerId = $tags['owner_id'] ?? null;

            if ($ownerId) {
                // Determine the type from the category mapping
                $mapping = DB::table('category_tax_mappings')
                    ->where('category_id', $transaction->category_id)
                    ->where('tax_form_code', 'form_5472')
                    ->first();

                if ($mapping) {
                    DB::table('related_party_transactions')->insert([
                        'transaction_date' => $transaction->transaction_date,
                        'amount' => $transaction->original_amount,
                        'type' => $mapping->line_item,
                        'owner_id' => $ownerId,
                        'account_id' => $transaction->account_id,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ]);
                }
            }
        }

        // Delete the migrated transactions
        DB::table('transactions')
            ->where('import_source', 'related_party_migration')
            ->delete();
    }
};
