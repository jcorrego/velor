<?php

use App\Enums\Finance\TaxFormCode;
use App\Finance\Services\SpainTaxReportingService;
use App\Models\Account;
use App\Models\CategoryTaxMapping;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getIrpfSummary aggregates EUR income and expenses by mapped categories and sources', function () {
    $eur = Currency::factory()->euro()->create();
    $usd = Currency::factory()->usd()->create();

    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->euro()->create(['entity_id' => $entity->id, 'currency_id' => $eur->id]);

    $incomeCategory = TransactionCategory::factory()->income()->create();
    $expenseCategory = TransactionCategory::factory()->expense()->create();

    CategoryTaxMapping::factory()->create([
        'category_id' => $incomeCategory->id,
        'tax_form_code' => TaxFormCode::IRPF,
        'line_item' => 'Rendimientos del trabajo',
        'country' => 'Spain',
    ]);

    CategoryTaxMapping::factory()->create([
        'category_id' => $expenseCategory->id,
        'tax_form_code' => TaxFormCode::IRPF,
        'line_item' => 'Gastos deducibles',
        'country' => 'Spain',
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-03-12',
        'original_amount' => 1000.00,
        'original_currency_id' => $eur->id,
    ]);

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-05-20',
        'original_amount' => 1000.00,
        'original_currency_id' => $usd->id,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-06-18',
        'original_amount' => -200.00,
        'original_currency_id' => $eur->id,
    ]);

    $unmappedCategory = TransactionCategory::factory()->income()->create();
    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $unmappedCategory->id,
        'transaction_date' => '2024-07-02',
        'original_amount' => 9000.00,
        'original_currency_id' => $eur->id,
    ]);

    $service = app(SpainTaxReportingService::class);
    $summary = $service->getIrpfSummary($user, 2024);

    $incomeSourceLabel = 'IRPF (Spanish Personal Income Tax): Rendimientos del trabajo';
    $expenseSourceLabel = 'IRPF (Spanish Personal Income Tax): Gastos deducibles';

    expect($summary['income_total'])->toBe(1909.00)
        ->and($summary['expense_total'])->toBe(-200.00)
        ->and($summary['net_income'])->toBe(1709.00)
        ->and($summary['income_by_category'][$incomeCategory->name])->toBe(1909.00)
        ->and($summary['expense_by_category'][$expenseCategory->name])->toBe(-200.00)
        ->and($summary['income_by_source'][$incomeSourceLabel])->toBe(1909.00)
        ->and($summary['expense_by_source'][$expenseSourceLabel])->toBe(-200.00);
});
