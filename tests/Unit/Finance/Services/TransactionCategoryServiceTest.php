<?php

use App\Enums\Finance\TaxFormCode;
use App\Models\Account;
use App\Models\CategoryTaxMapping;
use App\Models\Entity;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\Finance\TransactionCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aggregates totals by category for a tax year and jurisdiction', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $incomeCategory = TransactionCategory::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'income_or_expense' => 'income',
        'name' => 'Rental Income',
    ]);

    $expenseCategory = TransactionCategory::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'income_or_expense' => 'expense',
        'name' => 'Rental Expenses',
    ]);

    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-02-01',
        'converted_amount' => 1200,
    ]);

    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-03-01',
        'converted_amount' => 300,
    ]);

    $service = new TransactionCategoryService;
    $result = $service->aggregateByCategory(2024, $jurisdiction->id);

    expect($result)->toHaveCount(2)
        ->and(collect($result)->pluck('total')->sum())->toBe(1500.0);
});

it('computes tax form amounts for a filing', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2024,
    ]);

    $filingType = FilingType::factory()->create(['jurisdiction_id' => $jurisdiction->id]);
    $filing = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
    ]);

    $category = TransactionCategory::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'income_or_expense' => 'income',
    ]);

    CategoryTaxMapping::factory()->create([
        'category_id' => $category->id,
        'tax_form_code' => TaxFormCode::ScheduleE,
        'line_item' => 'Line 1',
        'country' => 'USA',
    ]);

    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $category->id,
        'transaction_date' => '2024-05-01',
        'converted_amount' => 500,
    ]);

    $service = new TransactionCategoryService;
    $result = $service->computeTaxFormAmounts(2024, $filing->id);

    expect($result['line_items'])->toHaveCount(1)
        ->and($result['line_items'][0]['tax_form_code'])->toBe(TaxFormCode::ScheduleE->value)
        ->and($result['line_items'][0]['amount'])->toBe(500.0);
});

it('validates category mappings', function () {
    $category = TransactionCategory::factory()->create();

    CategoryTaxMapping::factory()->create([
        'category_id' => $category->id,
        'tax_form_code' => TaxFormCode::ScheduleE,
        'line_item' => 'Line 1',
        'country' => 'USA',
    ]);

    $service = new TransactionCategoryService;

    expect($service->validateMappings($category->id))->toBeTrue()
        ->and($service->validateMappings(999999))->toBeFalse();
});
