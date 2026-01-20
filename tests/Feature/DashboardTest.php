<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertOk();
});

test('dashboard shows filing status summary and due dates', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create(['name' => 'United States']);
    $taxYear = TaxYear::factory()->for($jurisdiction)->create(['year' => 2025]);
    $filingTypePlanning = FilingType::factory()->for($jurisdiction)->create(['name' => 'Form 5472']);
    $filingTypeReview = FilingType::factory()->for($jurisdiction)->create(['name' => 'Schedule E']);
    $filingTypeFiled = FilingType::factory()->for($jurisdiction)->create(['name' => 'FinCEN 114']);

    Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingTypePlanning)
        ->planning()
        ->create([
            'key_metrics' => ['due_date' => '2026-04-15'],
        ]);

    Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingTypeReview)
        ->inReview()
        ->create([
            'key_metrics' => ['due_date' => '2026-02-01'],
        ]);

    Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingTypeFiled)
        ->filed()
        ->create();

    $eur = Currency::factory()->euro()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create(['currency_id' => $eur->id]);

    $incomeCategory = TransactionCategory::factory()->create([
        'name' => 'Consulting Income',
        'income_or_expense' => 'income',
    ]);

    $expenseCategory = TransactionCategory::factory()->create([
        'name' => 'Software Subscriptions',
        'income_or_expense' => 'expense',
    ]);

    $categoryTotalsDate = now()->subYear()->toDateString();

    Transaction::factory()->income()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => $categoryTotalsDate,
        'original_amount' => 1200.50,
        'original_currency_id' => $eur->id,
        'converted_amount' => 1200.50,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 1.0,
    ]);

    Transaction::factory()->expense()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => $categoryTotalsDate,
        'original_amount' => 300.25,
        'original_currency_id' => $eur->id,
        'converted_amount' => 300.25,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 1.0,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Filing status')
        ->assertSee('Upcoming due dates')
        ->assertSee('Apr 15, 2026')
        ->assertSee('Feb 01, 2026')
        ->assertSee('Category totals')
        ->assertSee('Consulting Income')
        ->assertSee('Software Subscriptions')
        ->assertSee('€'.number_format(1200.50, 2))
        ->assertSee('€'.number_format(300.25, 2));
});
