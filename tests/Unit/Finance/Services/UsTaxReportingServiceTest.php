<?php

use App\Enums\Finance\TaxFormCode;
use App\Finance\Services\UsTaxReportingService;
use App\Models\Account;
use App\Models\Asset;
use App\Models\CategoryTaxMapping;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\YearEndValue;
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
        'original_amount' => -2000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $drawCategory->id,
        'transaction_date' => '2024-09-15',
        'original_amount' => -3000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['draws'])->toBe(-5000.00);
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
        'original_amount' => -3000.00,
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

    expect($summary['related_party_totals'])->toBe(7500.00);
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

    CategoryTaxMapping::create([
        'category_id' => $rentalIncomeCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_1',
        'country' => 'USA',
    ]);

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

    CategoryTaxMapping::create([
        'category_id' => $maintenanceCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_18',
        'country' => 'USA',
    ]);

    $utilitiesCategory = TransactionCategory::factory()
        ->create([
            'name' => 'Rental Utilities',
            'income_or_expense' => 'expense',
        ]);

    CategoryTaxMapping::create([
        'category_id' => $utilitiesCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_18',
        'country' => 'USA',
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $maintenanceCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => -500.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => -454.50,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $utilitiesCategory->id,
        'transaction_date' => '2024-04-15',
        'original_amount' => -300.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => -272.70,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['expenses_by_category'])->toHaveKey('Rental Property Maintenance')
        ->and($summary['expenses_by_category']['Rental Property Maintenance'])->toBe(-500.00)
        ->and($summary['expenses_by_category'])->toHaveKey('Rental Utilities')
        ->and($summary['expenses_by_category']['Rental Utilities'])->toBe(-300.00);
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

    CategoryTaxMapping::create([
        'category_id' => $expenseCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_18',
        'country' => 'USA',
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => -1200.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => -1090.80,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-08-20',
        'original_amount' => -800.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => -727.20,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['total_expenses'])->toBe(-2000.00);
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

    CategoryTaxMapping::create([
        'category_id' => $incomeCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_1',
        'country' => 'USA',
    ]);

    $expenseCategory = TransactionCategory::factory()
        ->propertyMaintenance()
        ->create();

    CategoryTaxMapping::create([
        'category_id' => $expenseCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_18',
        'country' => 'USA',
    ]);

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
        'original_amount' => -2000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => -1818.00,
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

    CategoryTaxMapping::create([
        'category_id' => $incomeCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_1',
        'country' => 'USA',
    ]);

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

    CategoryTaxMapping::create([
        'category_id' => $expenseCategory->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'line_18',
        'country' => 'USA',
    ]);

    // Expense from asset's entity - should be included
    Transaction::factory()->expense()->create([
        'account_id' => $account1->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-10',
        'original_amount' => -1000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => -909.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    // Expense from different entity - should be excluded
    Transaction::factory()->expense()->create([
        'account_id' => $account2->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-04-15',
        'original_amount' => -3000.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => -2727.00,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.909,
    ]);

    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = app(UsTaxReportingService::class);
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['total_expenses'])->toBe(-1000.00);
});

test('getForm1040NrSummary groups totals by line item', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $incomeCategory = TransactionCategory::factory()->create([
        'name' => 'US Nonresident Income',
        'income_or_expense' => 'income',
    ]);

    $expenseCategory = TransactionCategory::factory()->create([
        'name' => 'US Nonresident Deductions',
        'income_or_expense' => 'expense',
    ]);

    CategoryTaxMapping::create([
        'category_id' => $incomeCategory->id,
        'tax_form_code' => TaxFormCode::Form1040NR->value,
        'line_item' => 'line_1',
        'country' => 'USA',
    ]);

    CategoryTaxMapping::create([
        'category_id' => $expenseCategory->id,
        'tax_form_code' => TaxFormCode::Form1040NR->value,
        'line_item' => 'line_10',
        'country' => 'USA',
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-02-10',
        'original_amount' => 3000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-05',
        'original_amount' => -500.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getForm1040NrSummary($user, 2024);

    expect($summary['line_items'])->toHaveKey('line_1')
        ->and($summary['line_items']['line_1'])->toBe(3000.00)
        ->and($summary['line_items'])->toHaveKey('line_10')
        ->and($summary['line_items']['line_10'])->toBe(-500.00)
        ->and($summary['total'])->toBe(2500.00);
});

test('getForm1040NrSummary excludes unmapped categories', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $mappedCategory = TransactionCategory::factory()->create([
        'name' => 'US Nonresident Income',
        'income_or_expense' => 'income',
    ]);

    $unmappedCategory = TransactionCategory::factory()->create([
        'name' => 'Unmapped Income',
        'income_or_expense' => 'income',
    ]);

    CategoryTaxMapping::create([
        'category_id' => $mappedCategory->id,
        'tax_form_code' => TaxFormCode::Form1040NR->value,
        'line_item' => 'line_1',
        'country' => 'USA',
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $mappedCategory->id,
        'transaction_date' => '2024-04-10',
        'original_amount' => 1200.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $unmappedCategory->id,
        'transaction_date' => '2024-04-15',
        'original_amount' => 900.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getForm1040NrSummary($user, 2024);

    expect($summary['line_items'])->toHaveKey('line_1')
        ->and($summary['line_items'])->toHaveCount(1)
        ->and($summary['total'])->toBe(1200.00);
});

test('getForm1120Summary groups totals by line item', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $incomeCategory = TransactionCategory::factory()->create([
        'name' => 'US Corporate Income',
        'income_or_expense' => 'income',
    ]);

    $expenseCategory = TransactionCategory::factory()->create([
        'name' => 'US Corporate Deductions',
        'income_or_expense' => 'expense',
    ]);

    CategoryTaxMapping::create([
        'category_id' => $incomeCategory->id,
        'tax_form_code' => TaxFormCode::Form1120->value,
        'line_item' => 'line_1',
        'country' => 'USA',
    ]);

    CategoryTaxMapping::create([
        'category_id' => $expenseCategory->id,
        'tax_form_code' => TaxFormCode::Form1120->value,
        'line_item' => 'line_26',
        'country' => 'USA',
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-02-10',
        'original_amount' => 8000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-05',
        'original_amount' => -1200.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getForm1120Summary($user, 2024);

    expect($summary['line_items'])->toHaveKey('line_1')
        ->and($summary['line_items']['line_1'])->toBe(8000.00)
        ->and($summary['line_items'])->toHaveKey('line_26')
        ->and($summary['line_items']['line_26'])->toBe(-1200.00)
        ->and($summary['total'])->toBe(6800.00);
});

test('getForm1120Summary excludes unmapped categories', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $mappedCategory = TransactionCategory::factory()->create([
        'name' => 'US Corporate Income',
        'income_or_expense' => 'income',
    ]);

    $unmappedCategory = TransactionCategory::factory()->create([
        'name' => 'Unmapped Corporate Income',
        'income_or_expense' => 'income',
    ]);

    CategoryTaxMapping::create([
        'category_id' => $mappedCategory->id,
        'tax_form_code' => TaxFormCode::Form1120->value,
        'line_item' => 'line_1',
        'country' => 'USA',
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $mappedCategory->id,
        'transaction_date' => '2024-04-10',
        'original_amount' => 1800.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $unmappedCategory->id,
        'transaction_date' => '2024-04-15',
        'original_amount' => 900.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $service = app(UsTaxReportingService::class);
    $summary = $service->getForm1120Summary($user, 2024);

    expect($summary['line_items'])->toHaveKey('line_1')
        ->and($summary['line_items'])->toHaveCount(1)
        ->and($summary['total'])->toBe(1800.00);
});

test('getForm5472YearEndTotals returns year-end totals by entity for the filing jurisdiction', function () {
    $user = User::factory()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $colombia = Jurisdiction::factory()->colombia()->create();
    $usaTaxYear = TaxYear::factory()->create(['jurisdiction_id' => $usa->id, 'year' => 2024]);
    $colombiaTaxYear = TaxYear::factory()->create(['jurisdiction_id' => $colombia->id, 'year' => 2024]);

    $usaEntityOne = Entity::factory()->create(['user_id' => $user->id, 'jurisdiction_id' => $usa->id, 'name' => 'Alpha LLC']);
    $usaEntityTwo = Entity::factory()->create(['user_id' => $user->id, 'jurisdiction_id' => $usa->id, 'name' => 'Beta LLC']);
    $colombiaEntity = Entity::factory()->create(['user_id' => $user->id, 'jurisdiction_id' => $colombia->id]);

    $usaAccountOne = Account::factory()->create(['entity_id' => $usaEntityOne->id]);
    $usaAssetOne = Asset::factory()->create(['entity_id' => $usaEntityOne->id]);
    $usaAccountTwo = Account::factory()->create(['entity_id' => $usaEntityTwo->id]);
    $colombiaAccount = Account::factory()->create(['entity_id' => $colombiaEntity->id]);

    YearEndValue::factory()->create([
        'entity_id' => $usaEntityOne->id,
        'tax_year_id' => $usaTaxYear->id,
        'account_id' => $usaAccountOne->id,
        'asset_id' => null,
        'amount' => 12000.00,
    ]);

    YearEndValue::factory()->forAsset()->create([
        'entity_id' => $usaEntityOne->id,
        'tax_year_id' => $usaTaxYear->id,
        'account_id' => null,
        'asset_id' => $usaAssetOne->id,
        'amount' => 8000.00,
    ]);

    YearEndValue::factory()->create([
        'entity_id' => $usaEntityTwo->id,
        'tax_year_id' => $usaTaxYear->id,
        'account_id' => $usaAccountTwo->id,
        'asset_id' => null,
        'amount' => 5000.00,
    ]);

    YearEndValue::factory()->create([
        'entity_id' => $colombiaEntity->id,
        'tax_year_id' => $colombiaTaxYear->id,
        'account_id' => $colombiaAccount->id,
        'asset_id' => null,
        'amount' => 9999.00,
    ]);

    $service = app(UsTaxReportingService::class);
    $totals = $service->getForm5472YearEndTotals($user, 2024);

    expect($totals['total'])->toBe(25000.00);

    $alphaTotals = collect($totals['entities'])->firstWhere('entity_id', $usaEntityOne->id);
    $betaTotals = collect($totals['entities'])->firstWhere('entity_id', $usaEntityTwo->id);

    expect($totals['entities'])->toHaveCount(2)
        ->and($alphaTotals['accounts_total'])->toBe(12000.00)
        ->and($alphaTotals['assets_total'])->toBe(8000.00)
        ->and($alphaTotals['total'])->toBe(20000.00)
        ->and($betaTotals['accounts_total'])->toBe(5000.00)
        ->and($betaTotals['assets_total'])->toBe(0.00)
        ->and($betaTotals['total'])->toBe(5000.00);
});
