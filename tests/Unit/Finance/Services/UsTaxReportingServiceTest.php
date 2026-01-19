<?php

use App\Finance\Services\UsTaxReportingService;
use App\Models\Account;
use App\Models\Asset;
use App\Models\CategoryTaxMapping;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getOwnerFlowSummary calculates contributions for tax year', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    // Create category and Form 5472 mapping for owner contributions
    $contributionCategory = TransactionCategory::firstOrCreate(
        ['name' => 'Owner Contribution'],
        ['income_or_expense' => 'income', 'sort_order' => 100]
    );
    CategoryTaxMapping::firstOrCreate(
        [
            'category_id' => $contributionCategory->id,
            'tax_form_code' => 'form_5472',
            'line_item' => 'owner_contribution',
        ],
        ['country' => 'USA']
    );

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $contributionCategory->id,
        'transaction_date' => '2024-02-15',
        'original_amount' => 10000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $contributionCategory->id,
        'transaction_date' => '2024-08-20',
        'original_amount' => 5000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['contributions'])->toBe(15000.00);
});

test('getOwnerFlowSummary calculates draws for tax year', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    // Create category and Form 5472 mapping for owner draws
    $drawCategory = TransactionCategory::firstOrCreate(
        ['name' => 'Owner Draw'],
        ['income_or_expense' => 'expense', 'sort_order' => 101]
    );
    CategoryTaxMapping::firstOrCreate(
        [
            'category_id' => $drawCategory->id,
            'tax_form_code' => 'form_5472',
            'line_item' => 'owner_draw',
        ],
        ['country' => 'USA']
    );

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $drawCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => 2000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $drawCategory->id,
        'transaction_date' => '2024-09-15',
        'original_amount' => 3000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['draws'])->toBe(5000.00);
});

test('getOwnerFlowSummary calculates total related party transactions', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    // Create categories and Form 5472 mappings
    $contributionCategory = TransactionCategory::firstOrCreate(
        ['name' => 'Owner Contribution'],
        ['income_or_expense' => 'income', 'sort_order' => 100]
    );
    CategoryTaxMapping::firstOrCreate(
        [
            'category_id' => $contributionCategory->id,
            'tax_form_code' => 'form_5472',
            'line_item' => 'owner_contribution',
        ],
        ['country' => 'USA']
    );

    $drawCategory = TransactionCategory::firstOrCreate(
        ['name' => 'Owner Draw'],
        ['income_or_expense' => 'expense', 'sort_order' => 101]
    );
    CategoryTaxMapping::firstOrCreate(
        [
            'category_id' => $drawCategory->id,
            'tax_form_code' => 'form_5472',
            'line_item' => 'owner_draw',
        ],
        ['country' => 'USA']
    );

    $reimbursementCategory = TransactionCategory::firstOrCreate(
        ['name' => 'Reimbursement'],
        ['income_or_expense' => 'income', 'sort_order' => 102]
    );
    CategoryTaxMapping::firstOrCreate(
        [
            'category_id' => $reimbursementCategory->id,
            'tax_form_code' => 'form_5472',
            'line_item' => 'reimbursement',
        ],
        ['country' => 'USA']
    );

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $contributionCategory->id,
        'transaction_date' => '2024-01-10',
        'original_amount' => 10000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $drawCategory->id,
        'transaction_date' => '2024-06-15',
        'original_amount' => 3000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $reimbursementCategory->id,
        'transaction_date' => '2024-08-20',
        'original_amount' => 500.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['related_party_totals'])->toBe(13500.00);
});

test('getOwnerFlowSummary filters by tax year', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    // Create category and Form 5472 mapping
    $contributionCategory = TransactionCategory::firstOrCreate(
        ['name' => 'Owner Contribution'],
        ['income_or_expense' => 'income', 'sort_order' => 100]
    );
    CategoryTaxMapping::firstOrCreate(
        [
            'category_id' => $contributionCategory->id,
            'tax_form_code' => 'form_5472',
            'line_item' => 'owner_contribution',
        ],
        ['country' => 'USA']
    );

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $contributionCategory->id,
        'transaction_date' => '2023-12-15',
        'original_amount' => 5000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $contributionCategory->id,
        'transaction_date' => '2024-01-15',
        'original_amount' => 10000.00,
        'original_currency_id' => $account->currency_id,
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
        ->create();

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
            'name' => 'Rental Property Maintenance',
            'income_or_expense' => 'expense',
        ]);

    $utilitiesCategory = TransactionCategory::factory()
        ->create([
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
        ->create();

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
        ->create();

    $expenseCategory = TransactionCategory::factory()
        ->propertyMaintenance()
        ->create();

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
        ->create();

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
        ->create();

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
