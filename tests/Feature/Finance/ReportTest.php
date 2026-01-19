<?php

use App\Models\Account;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns rental property report summary', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $asset = Asset::factory()->residential()->create(['entity_id' => $entity->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $incomeCategory = TransactionCategory::factory()->create([
        'jurisdiction_id' => $entity->jurisdiction_id,
        'income_or_expense' => 'income',
        'name' => 'Rental Income',
    ]);

    $expenseCategory = TransactionCategory::factory()->create([
        'jurisdiction_id' => $entity->jurisdiction_id,
        'income_or_expense' => 'expense',
        'name' => 'Rental Maintenance',
    ]);

    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'transaction_date' => '2024-04-15',
        'converted_amount' => 1200.00,
        'type' => 'income',
    ]);

    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'transaction_date' => '2024-06-20',
        'converted_amount' => 300.00,
        'type' => 'expense',
    ]);

    $response = $this->actingAs($user)->getJson("/api/reports/rental-properties/{$asset->id}?year=2024");

    $response->assertSuccessful()
        ->assertJsonPath('asset_id', $asset->id)
        ->assertJsonPath('year', 2024)
        ->assertJsonPath('income', 1200)
        ->assertJsonPath('expenses', 300);
});

it('rejects report access for another users asset', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $otherUser->id]);
    $asset = Asset::factory()->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->getJson("/api/reports/rental-properties/{$asset->id}?year=2024");

    $response->assertForbidden();
});
