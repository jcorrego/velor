<?php

use App\Enums\Finance\TaxFormCode;
use App\Finance\Services\ColombiaTaxReportingService;
use App\Models\Account;
use App\Models\CategoryTaxMapping;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getIncomeExpenseSummary aggregates COP income and expenses by mapped categories', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->bancolombia()->create(['entity_id' => $entity->id]);

    $incomeCategory = TransactionCategory::factory()->income()->create();
    $expenseCategory = TransactionCategory::factory()->expense()->create();

    CategoryTaxMapping::factory()->create([
        'category_id' => $incomeCategory->id,
        'tax_form_code' => TaxFormCode::ColombianDeclaration,
        'line_item' => 'income',
        'country' => 'Colombia',
    ]);

    CategoryTaxMapping::factory()->create([
        'category_id' => $expenseCategory->id,
        'tax_form_code' => TaxFormCode::ColombianDeclaration,
        'line_item' => 'expense',
        'country' => 'Colombia',
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-03-12',
        'original_amount' => 10000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-05-21',
        'original_amount' => -2500.00,
        'original_currency_id' => $account->currency_id,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2023-11-10',
        'original_amount' => 5000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $unmappedCategory = TransactionCategory::factory()->income()->create();
    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $unmappedCategory->id,
        'transaction_date' => '2024-07-02',
        'original_amount' => 9000.00,
        'original_currency_id' => $account->currency_id,
    ]);

    $usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
    );
    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-09-18',
        'original_amount' => 1200.00,
        'original_currency_id' => $usd->id,
    ]);

    $service = app(ColombiaTaxReportingService::class);
    $summary = $service->getIncomeExpenseSummary($user, 2024);

    expect($summary['income_total'])->toBe(10000.00)
        ->and($summary['expense_total'])->toBe(-2500.00)
        ->and($summary['net_income'])->toBe(7500.00)
        ->and($summary['income_by_category'][$incomeCategory->name])->toBe(10000.00)
        ->and($summary['expense_by_category'][$expenseCategory->name])->toBe(-2500.00);
});
