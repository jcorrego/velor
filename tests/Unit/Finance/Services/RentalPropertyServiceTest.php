<?php

use App\Finance\Services\RentalPropertyService;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getAnnualRentalIncome calculates income for year', function () {
    $entity = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $rentalIncomeCategory = TransactionCategory::factory()
        ->rentalIncome()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    Transaction::factory()
        ->income()
        ->create([
            'account_id' => $account->id,
            'category_id' => $rentalIncomeCategory->id,
            'transaction_date' => '2024-01-15',
            'converted_amount' => 1500.00,
        ]);

    Transaction::factory()
        ->income()
        ->create([
            'account_id' => $account->id,
            'category_id' => $rentalIncomeCategory->id,
            'transaction_date' => '2024-06-15',
            'converted_amount' => 1500.00,
        ]);

    $asset->rentalIncomeCategories = [$rentalIncomeCategory->id];

    $service = new RentalPropertyService;
    $income = $service->getAnnualRentalIncome($asset, 2024);

    expect($income)->toBe(3000.00);
});

test('getAnnualRentalExpenses calculates expenses for year', function () {
    $entity = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $maintenanceCategory = TransactionCategory::factory()
        ->propertyMaintenance()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    Transaction::factory()
        ->expense()
        ->create([
            'account_id' => $account->id,
            'category_id' => $maintenanceCategory->id,
            'transaction_date' => '2024-03-10',
            'converted_amount' => 500.00,
        ]);

    Transaction::factory()
        ->expense()
        ->create([
            'account_id' => $account->id,
            'category_id' => $maintenanceCategory->id,
            'transaction_date' => '2024-08-20',
            'converted_amount' => 300.00,
        ]);

    $asset->rentalExpenseCategories = [$maintenanceCategory->id];

    $service = new RentalPropertyService;
    $expenses = $service->getAnnualRentalExpenses($asset, 2024);

    expect($expenses)->toBe(800.00);
});

test('getAnnualDepreciation returns depreciation amount', function () {
    $asset = Asset::factory()->residential()->create([
        'annual_depreciation_amount' => 10000.00,
    ]);

    $service = new RentalPropertyService;
    $depreciation = $service->getAnnualDepreciation($asset);

    expect($depreciation)->toBe(10000.00);
});

test('calculateNetRentalIncome subtracts income minus expenses minus depreciation', function () {
    $entity = Entity::factory()->create();
    $asset = Asset::factory()->residential()->create([
        'entity_id' => $entity->id,
        'annual_depreciation_amount' => 5000.00,
    ]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $incomeCategory = TransactionCategory::factory()
        ->rentalIncome()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    $expenseCategory = TransactionCategory::factory()
        ->propertyMaintenance()
        ->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    Transaction::factory()
        ->income()
        ->create([
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'transaction_date' => '2024-01-15',
            'converted_amount' => 18000.00,
        ]);

    Transaction::factory()
        ->expense()
        ->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'transaction_date' => '2024-03-10',
            'converted_amount' => 2000.00,
        ]);

    $asset->rentalIncomeCategories = [$incomeCategory->id];
    $asset->rentalExpenseCategories = [$expenseCategory->id];

    $service = new RentalPropertyService;
    $netIncome = $service->calculateNetRentalIncome($asset, 2024);

    expect($netIncome)->toBe(11000.00);
});

test('calculateAccumulatedDepreciation calculates total depreciation to date', function () {
    $asset = Asset::factory()->residential()->create([
        'acquisition_date' => '2020-01-01',
        'useful_life_years' => 27.5,
        'annual_depreciation_amount' => 10000.00,
    ]);

    $service = new RentalPropertyService;
    $asOfDate = Carbon::parse('2024-06-01');

    $accumulated = $service->calculateAccumulatedDepreciation($asset, $asOfDate);

    expect($accumulated)->toBeGreaterThan(40000.00)
        ->toBeLessThanOrEqual(50000.00);
});

test('getDepreciationPercentageRemaining calculates percentage left', function () {
    $asset = Asset::factory()->residential()->create([
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 275000.00,
        'useful_life_years' => 27.5,
        'annual_depreciation_amount' => 10000.00,
    ]);

    $service = new RentalPropertyService;
    $percentageRemaining = $service->getDepreciationPercentageRemaining($asset);

    expect($percentageRemaining)->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
});
