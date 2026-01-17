<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\Transaction;
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
