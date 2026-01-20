<?php

declare(strict_types=1);
use App\Models\Account;
use App\Models\DescriptionCategoryRule;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\Finance\TransactionImportService;

it('falls back to the account currency when import data is missing currency', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $service = app(TransactionImportService::class);

    $imported = $service->importTransactions([
        [
            'date' => now()->toDateString(),
            'description' => 'Missing currency',
            'amount' => -25.50,
            'original_currency' => '',
        ],
    ], $account, 'mercury');

    expect($imported)->toBe(1);

    $transaction = Transaction::query()->latest('id')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->original_currency_id)->toBe($account->currency_id);
});

it('applies counterparty from description rule during import', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $category = TransactionCategory::factory()->create();

    DescriptionCategoryRule::create([
        'jurisdiction_id' => $entity->jurisdiction_id,
        'category_id' => $category->id,
        'description_pattern' => 'AMAZON',
        'counterparty' => 'Amazon EU SARL',
        'is_active' => true,
    ]);

    $service = app(TransactionImportService::class);

    $service->importTransactions([
        [
            'date' => now()->toDateString(),
            'description' => 'AMAZON WEB SERVICES',
            'amount' => -45.00,
            'original_currency' => '',
            'counterparty' => 'Original Name',
        ],
    ], $account, 'mercury');

    $transaction = Transaction::query()->latest('id')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->category_id)->toBe($category->id)
        ->and($transaction->counterparty_name)->toBe('Amazon EU SARL');
});
