<?php

use App\Enums\Finance\TransactionType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\FxRate;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('list transactions with pagination 50 per page', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    Transaction::factory()
        ->count(75)
        ->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->getJson('/api/transactions');

    $response->assertSuccessful()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('meta.per_page', 50);
});

test('filter transactions by account_id', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account1 = Account::factory()->create(['entity_id' => $entity->id]);
    $account2 = Account::factory()->create(['entity_id' => $entity->id]);

    Transaction::factory()->count(5)->create(['account_id' => $account1->id]);
    Transaction::factory()->count(3)->create(['account_id' => $account2->id]);

    $response = $this->actingAs($user)->getJson("/api/transactions?account_id={$account1->id}");

    $response->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

test('create multi-currency transaction with FX conversion', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);

    $eur = Currency::firstOrCreate(['code' => 'EUR'], [
        'name' => 'Euro',
        'symbol' => '€',
        'is_active' => true,
    ]);
    $usd = Currency::firstOrCreate(['code' => 'USD'], [
        'name' => 'US Dollar',
        'symbol' => '$',
        'is_active' => true,
    ]);
    $account = Account::factory()->create([
        'entity_id' => $entity->id,
        'currency_id' => $eur->id,
    ]);
    $category = TransactionCategory::factory()->create(['jurisdiction_id' => $entity->jurisdiction_id]);

    // Clear any cached FX rates
    Cache::flush();

    FxRate::create([
        'currency_from_id' => $eur->id,
        'currency_to_id' => $usd->id,
        'rate' => 1.10,
        'rate_date' => '2024-01-15',
        'source' => 'ecb',
    ]);

    $data = [
        'transaction_date' => '2024-01-15',
        'account_id' => $account->id,
        'type' => TransactionType::Income->value,
        'original_amount' => 1000.00,
        'original_currency_id' => $eur->id,
        'converted_currency_id' => $usd->id,
        'category_id' => $category->id,
        'description' => 'Test multi-currency transaction',
    ];

    $response = $this->actingAs($user)->postJson('/api/transactions', $data);

    $response->assertStatus(201)
        ->assertJsonPath('original_amount', '1000.00')
        ->assertJsonPath('fx_rate', '1.10000000')
        ->assertJsonPath('converted_amount', '1100.00');
});

test('validation fails with invalid amount zero', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $category = TransactionCategory::factory()->create();

    $data = [
        'transaction_date' => '2024-01-15',
        'account_id' => $account->id,
        'type' => TransactionType::Income->value,
        'original_amount' => 0.00,
        'original_currency_id' => $account->currency_id,
        'category_id' => $category->id,
    ];

    $response = $this->actingAs($user)->postJson('/api/transactions', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['original_amount']);
});

test('allows negative transaction amount', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $eur = Currency::firstOrCreate(['code' => 'EUR'], [
        'name' => 'Euro',
        'symbol' => '€',
        'is_active' => true,
    ]);
    $account = Account::factory()->create([
        'entity_id' => $entity->id,
        'currency_id' => $eur->id,
    ]);

    $data = [
        'transaction_date' => '2024-01-15',
        'account_id' => $account->id,
        'type' => TransactionType::Expense->value,
        'original_amount' => -50.00,
        'original_currency_id' => $eur->id,
        'description' => 'Refund adjustment',
    ];

    $response = $this->actingAs($user)->postJson('/api/transactions', $data);

    $response->assertStatus(201)
        ->assertJsonPath('original_amount', '-50.00')
        ->assertJsonPath('converted_amount', '-50.00');
});

test('view transaction details', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $transaction = Transaction::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->getJson("/api/transactions/{$transaction->id}");

    $response->assertSuccessful()
        ->assertJsonPath('id', $transaction->id)
        ->assertJsonPath('description', $transaction->description);
});

test('update transaction category', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $category1 = TransactionCategory::factory()->create();
    $category2 = TransactionCategory::factory()->create();
    $transaction = Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $category1->id,
    ]);

    $response = $this->actingAs($user)->putJson("/api/transactions/{$transaction->id}", [
        'category_id' => $category2->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('category_id', $category2->id);

    $transaction->refresh();
    expect($transaction->category_id)->toBe($category2->id);
});

test('update transaction tags', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $transaction = Transaction::factory()->create([
        'account_id' => $account->id,
        'tags' => ['old', 'tags'],
    ]);

    $response = $this->actingAs($user)->putJson("/api/transactions/{$transaction->id}", [
        'tags' => ['new', 'updated', 'tags'],
    ]);

    $response->assertSuccessful();

    $transaction->refresh();
    expect($transaction->tags)->toBe(['new', 'updated', 'tags']);
});

test('cannot change transaction_date on update', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $transaction = Transaction::factory()->create([
        'account_id' => $account->id,
        'transaction_date' => '2024-01-01',
    ]);

    $response = $this->actingAs($user)->putJson("/api/transactions/{$transaction->id}", [
        'transaction_date' => '2024-02-01',
    ]);

    $transaction->refresh();
    expect($transaction->transaction_date->format('Y-m-d'))->toBe('2024-01-01');
});

test('mark transaction as reconciled', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $transaction = Transaction::factory()->create([
        'account_id' => $account->id,
        'reconciled_at' => null,
    ]);

    $response = $this->actingAs($user)->postJson("/api/transactions/{$transaction->id}/reconcile");

    $response->assertSuccessful();

    $transaction->refresh();
    expect($transaction->reconciled_at)->not->toBeNull();
});

test('delete transaction', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $transaction = Transaction::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->deleteJson("/api/transactions/{$transaction->id}");

    $response->assertStatus(204);
    expect(Transaction::find($transaction->id))->toBeNull();
});
