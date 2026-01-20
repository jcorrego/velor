<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('can filter transactions by uncategorized', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $category = TransactionCategory::factory()->create();
    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $category->id,
    ]);

    $uncategorized = Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => null,
    ]);
    $this->actingAs($user);
    $component = Livewire::test('finance.transaction-list');
    $component->set('filterCategoryId', 'uncategorized');
    $transactions = $component->viewData('transactions');
    expect($transactions)->toHaveCount(1)
        ->and($transactions->first()->id)->toBe($uncategorized->id);
});
