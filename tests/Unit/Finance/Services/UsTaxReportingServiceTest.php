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

    $service = new UsTaxReportingService;
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

    $service = new UsTaxReportingService;
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

    $service = new UsTaxReportingService;
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

    $service = new UsTaxReportingService;
    $summary = $service->getOwnerFlowSummary($user, 2024);

    expect($summary['contributions'])->toBe(10000.00);
});

test('getScheduleERentalSummary calculates rental income', function () {
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
        'converted_amount' => 2000.00,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $rentalIncomeCategory->id,
        'transaction_date' => '2024-07-15',
        'converted_amount' => 2000.00,
    ]);

    $asset->rentalIncomeCategories = [$rentalIncomeCategory->id];

    $service = new UsTaxReportingService;
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['rental_income'])->toBe(4000.00);
});

test('getScheduleERentalSummary groups expenses by category', function () {
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
        'converted_amount' => 500.00,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $utilitiesCategory->id,
        'transaction_date' => '2024-04-15',
        'converted_amount' => 300.00,
    ]);

    $service = new UsTaxReportingService;
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['expenses_by_category'])->toHaveKey('Rental Property Maintenance')
        ->and($summary['expenses_by_category']['Rental Property Maintenance'])->toBe(500.00)
        ->and($summary['expenses_by_category'])->toHaveKey('Rental Utilities')
        ->and($summary['expenses_by_category']['Rental Utilities'])->toBe(300.00);
});

test('getScheduleERentalSummary calculates total expenses', function () {
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
        'converted_amount' => 1200.00,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-08-20',
        'converted_amount' => 800.00,
    ]);

    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = new UsTaxReportingService;
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['total_expenses'])->toBe(2000.00);
});

test('getScheduleERentalSummary calculates net income', function () {
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
        'converted_amount' => 5000.00,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-10',
        'converted_amount' => 2000.00,
    ]);

    $asset->rentalIncomeCategories = [$incomeCategory->id];
    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = new UsTaxReportingService;
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['net_income'])->toBe(3000.00);
});

test('getScheduleERentalSummary filters income by asset entity', function () {
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
        'converted_amount' => 2000.00,
    ]);

    // Income from different entity - should be excluded
    Transaction::factory()->income()->create([
        'account_id' => $account2->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-02-15',
        'converted_amount' => 5000.00,
    ]);

    $asset->rentalIncomeCategories = [$incomeCategory->id];

    $service = new UsTaxReportingService;
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['rental_income'])->toBe(2000.00);
});

test('getScheduleERentalSummary filters expenses by asset entity', function () {
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
        'converted_amount' => 1000.00,
    ]);

    // Expense from different entity - should be excluded
    Transaction::factory()->expense()->create([
        'account_id' => $account2->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-04-15',
        'converted_amount' => 3000.00,
    ]);

    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = new UsTaxReportingService;
    $summary = $service->getScheduleERentalSummary($asset, 2024);

    expect($summary['total_expenses'])->toBe(1000.00);
});
