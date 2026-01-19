<?php

use App\Enums\Finance\RelatedPartyType;
use App\Finance\Services\UsTaxReportingService;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\RelatedPartyTransaction;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getOwnerFlowSummary calculates contributions for tax year', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerContribution,
        'transaction_date' => '2024-02-15',
        'amount' => 10000.00,
    ]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerContribution,
        'transaction_date' => '2024-08-20',
        'amount' => 5000.00,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['contributions'])->toBe(15000.00);
});

test('getOwnerFlowSummary calculates draws for tax year', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerDraw,
        'transaction_date' => '2024-03-10',
        'amount' => 2000.00,
    ]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerDraw,
        'transaction_date' => '2024-09-15',
        'amount' => 3000.00,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['draws'])->toBe(5000.00);
});

test('getOwnerFlowSummary calculates total related party transactions', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerContribution,
        'transaction_date' => '2024-01-10',
        'amount' => 10000.00,
    ]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerDraw,
        'transaction_date' => '2024-06-15',
        'amount' => 3000.00,
    ]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::Reimbursement,
        'transaction_date' => '2024-08-20',
        'amount' => 500.00,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['related_party_totals'])->toBe(13500.00);
});

test('getOwnerFlowSummary filters by tax year', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerContribution,
        'transaction_date' => '2023-12-15',
        'amount' => 5000.00,
    ]);

    RelatedPartyTransaction::factory()->create([
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'type' => RelatedPartyType::OwnerContribution,
        'transaction_date' => '2024-01-15',
        'amount' => 10000.00,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['contributions'])->toBe(10000.00);
});

test('getScheduleERentalSummary calculates rental income', function () {
    $usd = \App\Models\Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
    $eur = \App\Models\Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);

    $entity = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $rentalIncomeCategory = TransactionCategory::factory()
        ->rentalIncome()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $rentalIncomeCategory->id,
        'transaction_date' => '2024-01-15',
        'original_amount' => 2000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 1818.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $rentalIncomeCategory->id,
        'transaction_date' => '2024-07-15',
        'original_amount' => 2000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 1818.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $asset->rentalIncomeCategories = [$rentalIncomeCategory->id];

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['rental_income'])->toBe(4000.00);
});

test('getScheduleERentalSummary groups expenses by category', function () {
    $usd = \App\Models\Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
    $eur = \App\Models\Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);

    $entity = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $maintenanceCategory = TransactionCategory::factory()
        ->create([
            'jurisdiction_id' => $entity->jurisdiction_id,
            'name' => 'Rental Property Maintenance',
            'income_or_expense' => 'expense',
        ]);

    $utilitiesCategory = TransactionCategory::factory()
        ->create([
            'jurisdiction_id' => $entity->jurisdiction_id,
            'name' => 'Rental Utilities',
            'income_or_expense' => 'expense',
        ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $maintenanceCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => 500.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 454.50,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $utilitiesCategory->id,
        'transaction_date' => '2024-04-15',
        'original_amount' => 300.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 272.70,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['expenses_by_category'])->toHaveKey('Rental Property Maintenance')
        ->and($summary['expenses_by_category']['Rental Property Maintenance'])->toBe(500.00)
        ->and($summary['expenses_by_category'])->toHaveKey('Rental Utilities')
        ->and($summary['expenses_by_category']['Rental Utilities'])->toBe(300.00);
});

test('getScheduleERentalSummary calculates total expenses', function () {
    $usd = \App\Models\Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
    $eur = \App\Models\Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);

    $entity = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $expenseCategory = TransactionCategory::factory()
        ->propertyMaintenance()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => 1200.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 1090.80,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-08-20',
        'original_amount' => 800.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 727.20,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['total_expenses'])->toBe(2000.00);
});

test('getScheduleERentalSummary calculates net income', function () {
    $usd = \App\Models\Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
    $eur = \App\Models\Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);

    $entity = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $incomeCategory = TransactionCategory::factory()
        ->rentalIncome()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    $expenseCategory = TransactionCategory::factory()
        ->propertyMaintenance()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-01-15',
        'original_amount' => 5000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 4545.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => 2000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 1818.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $asset->rentalIncomeCategories = [$incomeCategory->id];
    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['net_income'])->toBe(3000.00);
});

test('getScheduleERentalSummary filters income by asset entity', function () {
    $usd = \App\Models\Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
    $eur = \App\Models\Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);

    $entity1 = Entity::factory()->create();
    $entity2 = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity1->id]);
    $account1 = Account::factory()->create(['entity_id' => $entity1->id]);
    $account2 = Account::factory()->create(['entity_id' => $entity2->id]);

    $incomeCategory = TransactionCategory::factory()
        ->rentalIncome()
        ->create(['jurisdiction_id' => $entity1->jurisdiction_id]);

    // Income from asset's entity - should be included
    Transaction::factory()->income()->create([
        'account_id' => $account1->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-01-15',
        'original_amount' => 2000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 1818.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    // Income from different entity - should be excluded
    Transaction::factory()->income()->create([
        'account_id' => $account2->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-02-15',
        'original_amount' => 5000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 4545.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $asset->rentalIncomeCategories = [$incomeCategory->id];

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['rental_income'])->toBe(2000.00);
});

test('getScheduleERentalSummary filters expenses by asset entity', function () {
    $usd = \App\Models\Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
    $eur = \App\Models\Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]);

    $entity1 = Entity::factory()->create();
    $entity2 = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity1->id]);
    $account1 = Account::factory()->create(['entity_id' => $entity1->id]);
    $account2 = Account::factory()->create(['entity_id' => $entity2->id]);

    $expenseCategory = TransactionCategory::factory()
        ->propertyMaintenance()
        ->create(['jurisdiction_id' => $entity1->jurisdiction_id]);

    // Expense from asset's entity - should be included
    Transaction::factory()->expense()->create([
        'account_id' => $account1->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => 1000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 909.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    // Expense from different entity - should be excluded
    Transaction::factory()->expense()->create([
        'account_id' => $account2->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-04-15',
        'original_amount' => 3000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 2727.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['total_expenses'])->toBe(1000.00);
});
