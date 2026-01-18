<?php

namespace Database\Seeders;

use App\Enums\Finance\ImportBatchStatus;
use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\ImportMappingProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ImportBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        $account = Account::first();
        if (! $account) {
            return;
        }

        // Create pending batches for testing approval/rejection
        ImportBatch::factory()
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Pending,
                'transaction_count' => 15,
                'proposed_transactions' => [
                    [
                        'date' => '2024-01-15',
                        'description' => 'Invoice #1001',
                        'amount' => 1500.00,
                        'category' => 'Sales Revenue',
                    ],
                    [
                        'date' => '2024-01-16',
                        'description' => 'Office Supplies',
                        'amount' => -250.50,
                        'category' => 'Office Expenses',
                    ],
                ],
            ]);

        ImportBatch::factory()
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Pending,
                'transaction_count' => 8,
                'proposed_transactions' => [
                    [
                        'date' => '2024-01-17',
                        'description' => 'Client Payment',
                        'amount' => 5000.00,
                        'category' => 'Sales Revenue',
                    ],
                ],
            ]);

        // Create an approved batch
        ImportBatch::factory()
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Applied,
                'transaction_count' => 12,
                'approved_by' => $user->id,
                'approved_at' => now()->subHours(2),
            ]);

        // Create a rejected batch
        ImportBatch::factory()
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Rejected,
                'transaction_count' => 5,
                'rejection_reason' => 'Duplicate entries detected. Please review the source file and resubmit.',
            ]);

        // Create sample mapping profiles
        ImportMappingProfile::create([
            'account_id' => $account->id,
            'name' => 'Bank Export Format',
            'description' => 'Standard mapping for our bank CSV exports',
            'column_mapping' => [
                'Transaction Date' => 'date',
                'Description' => 'description',
                'Debit' => 'amount',
                'Category' => 'category',
            ],
        ]);

        ImportMappingProfile::create([
            'account_id' => $account->id,
            'name' => 'Accounting Software Export',
            'description' => 'Mapping for exports from accounting software',
            'column_mapping' => [
                'Date' => 'date',
                'Memo' => 'description',
                'Amount' => 'amount',
                'Account' => 'category',
                'Reference' => 'reference',
            ],
        ]);
    }
}
