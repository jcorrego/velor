<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Livewire\Livewire;

it('allows a user to override the FX rate for their transaction', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();
    $category = TransactionCategory::factory()->for($entity)->create();

    $transaction = Transaction::factory()
        ->forAccount($account)
        ->state([
            'category_id' => $category->id,
            'original_amount' => 100.00,
            'fx_rate' => 1.05,
            'fx_source' => 'ecb',
        ])
        ->create();

    $this->actingAs($user);

    Livewire::test('finance.transaction-list')
        ->call('openFxOverride', $transaction->id)
        ->assertSet('showFxOverrideModal', true)
        ->set('fxOverrideRate', '1.25')
        ->set('fxOverrideReason', 'manual override')
        ->call('saveFxOverride');

    $transaction->refresh();

    expect((float) $transaction->fx_rate)->toBe(1.25)
        ->and($transaction->fx_source)->toBe('manual override')
        ->and((float) $transaction->converted_amount)->toBe(125.0);
});
