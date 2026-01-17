<?php

use App\Enums\Finance\TransactionType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a manual transaction from the list', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $usd = Currency::factory()->usd()->create();
    $eur = Currency::factory()->euro()->create();
    $account = Account::factory()->for($entity)->create(['currency_id' => $usd->id]);
    $category = TransactionCategory::factory()->forEntity($entity)->create();

    $this->actingAs($user);

    Livewire::test('finance.transaction-list')
        ->set('transactionDate', '2024-01-15')
        ->set('transactionAccountId', $account->id)
        ->set('transactionType', TransactionType::Income->value)
        ->set('transactionAmount', '100.00')
        ->set('transactionCurrencyId', $usd->id)
        ->set('transactionCategoryId', $category->id)
        ->set('transactionCounterpartyName', 'Acme Inc.')
        ->set('transactionDescription', 'Manual transaction entry')
        ->call('saveTransaction')
        ->assertHasNoErrors();

    $transaction = Transaction::query()->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->account_id)->toBe($account->id)
        ->and($transaction->type)->toBe(TransactionType::Income)
        ->and((float) $transaction->original_amount)->toBe(100.0)
        ->and($transaction->original_currency_id)->toBe($usd->id)
        ->and($transaction->converted_currency_id)->toBe($eur->id)
        ->and((float) $transaction->fx_rate)->toBe(0.909)
        ->and((float) $transaction->converted_amount)->toBe(90.9)
        ->and($transaction->import_source)->toBe('manual');
});

it('allows negative amounts in the manual transaction form', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $eur = Currency::factory()->euro()->create();
    $account = Account::factory()->for($entity)->create(['currency_id' => $eur->id]);

    $this->actingAs($user);

    Livewire::test('finance.transaction-list')
        ->set('transactionDate', '2024-02-01')
        ->set('transactionAccountId', $account->id)
        ->set('transactionType', TransactionType::Expense->value)
        ->set('transactionAmount', '-10.50')
        ->set('transactionCurrencyId', $eur->id)
        ->call('saveTransaction')
        ->assertHasNoErrors();

    $transaction = Transaction::query()->latest('id')->first();

    expect($transaction)->not->toBeNull()
        ->and((float) $transaction->original_amount)->toBe(-10.5);
});

it('edits a manual transaction from the list', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $eur = Currency::factory()->euro()->create();
    $account = Account::factory()->for($entity)->create(['currency_id' => $eur->id]);
    $category = TransactionCategory::factory()->forEntity($entity)->create();
    $transaction = Transaction::factory()
        ->forAccount($account)
        ->state([
            'category_id' => $category->id,
            'original_amount' => 50.00,
            'original_currency_id' => $eur->id,
            'converted_amount' => 50.00,
            'converted_currency_id' => $eur->id,
            'fx_rate' => 1.0,
            'fx_source' => 'ecb',
            'type' => TransactionType::Expense,
        ])
        ->create();

    $this->actingAs($user);

    Livewire::test('finance.transaction-list')
        ->call('editTransaction', $transaction->id)
        ->set('transactionAmount', '200.00')
        ->set('transactionDescription', 'Updated entry')
        ->call('saveTransaction')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect((float) $transaction->original_amount)->toBe(200.0)
        ->and((float) $transaction->converted_amount)->toBe(200.0)
        ->and($transaction->description)->toBe('Updated entry');
});

it('prevents creating a transaction for another user account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherEntity = Entity::factory()->for($otherUser)->create();
    $usd = Currency::factory()->usd()->create();
    Currency::factory()->euro()->create();
    $otherAccount = Account::factory()->for($otherEntity)->create(['currency_id' => $usd->id]);

    $this->actingAs($user);

    Livewire::test('finance.transaction-list')
        ->set('transactionDate', '2024-01-15')
        ->set('transactionAccountId', $otherAccount->id)
        ->set('transactionType', TransactionType::Income->value)
        ->set('transactionAmount', '100.00')
        ->set('transactionCurrencyId', $usd->id)
        ->call('saveTransaction')
        ->assertStatus(403);
});
