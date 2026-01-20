<?php

declare(strict_types=1);

use App\Livewire\Finance\DescriptionCategoryRules;
use App\Models\Account;
use App\Models\DescriptionCategoryRule;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('preview shows only mismatched transactions and applies a single change', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();

    $entity = Entity::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $jurisdiction->id,
    ]);

    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $targetCategory = TransactionCategory::factory()->create();
    $otherCategory = TransactionCategory::factory()->create();

    $rule = DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $targetCategory->id,
        'description_pattern' => 'STAR',
        'is_active' => true,
    ]);

    $matchingTransaction = Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $otherCategory->id,
        'description' => 'STARBUCKS COFFEE',
    ]);

    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $targetCategory->id,
        'description' => 'STARBUCKS ALREADY',
    ]);

    $otherUser = User::factory()->create();
    $otherEntity = Entity::factory()->create([
        'user_id' => $otherUser->id,
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $otherAccount = Account::factory()->create(['entity_id' => $otherEntity->id]);

    Transaction::factory()->create([
        'account_id' => $otherAccount->id,
        'category_id' => $otherCategory->id,
        'description' => 'STARBUCKS OTHER USER',
    ]);

    Livewire::actingAs($user)
        ->test(DescriptionCategoryRules::class)
        ->call('previewExisting', $rule->id)
        ->assertCount('previewTransactions', 1)
        ->call('applyPreviewTransaction', $matchingTransaction->id)
        ->assertCount('previewTransactions', 0);

    expect($matchingTransaction->refresh()->category_id)->toBe($targetCategory->id);
});

test('apply all updates every previewed transaction', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();

    $entity = Entity::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $jurisdiction->id,
    ]);

    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $targetCategory = TransactionCategory::factory()->create();

    $rule = DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $targetCategory->id,
        'description_pattern' => 'AWS',
        'is_active' => true,
    ]);

    $first = Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => null,
        'description' => 'AWS BILLING',
    ]);

    $second = Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => null,
        'description' => 'AWS MARKETPLACE',
    ]);

    Livewire::actingAs($user)
        ->test(DescriptionCategoryRules::class)
        ->call('previewExisting', $rule->id)
        ->assertCount('previewTransactions', 2)
        ->call('applyAllPreviewTransactions')
        ->assertCount('previewTransactions', 0);

    expect($first->refresh()->category_id)->toBe($targetCategory->id)
        ->and($second->refresh()->category_id)->toBe($targetCategory->id);
});
