<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('list categories', function () {
    $user = User::factory()->create();
    
    $existingCount = TransactionCategory::count();
    
    TransactionCategory::factory()
        ->count(5)
        ->create();

    $response = $this->actingAs($user)->getJson('/api/transaction-categories');

    $response->assertSuccessful()
        ->assertJsonCount($existingCount + 5, 'data');
});

test('create category', function () {
    $user = User::factory()->create();
    $data = [
        'name' => 'Business Expenses',
        'income_or_expense' => 'expense',
        'sort_order' => 10,
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction-categories', $data);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'Business Expenses')
        ->assertJsonPath('income_or_expense', 'expense')
        ->assertJsonPath('sort_order', 10);
});

test('validation fails with duplicate name', function () {
    $user = User::factory()->create();
    TransactionCategory::factory()->create([
        'name' => 'Rental Income',
    ]);

    $data = [
        'name' => 'Rental Income',
        'income_or_expense' => 'income',
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction-categories', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('view category with tax mappings', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/transaction-categories/{$category->id}");

    $response->assertSuccessful()
        ->assertJsonPath('id', $category->id)
        ->assertJsonPath('name', $category->name)
        ->assertJsonStructure([
            'id',
            'name',
            'taxMappings',
        ]);
});

test('update category', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->create([
        'name' => 'Old Category Name',
        'sort_order' => 5,
    ]);

    $response = $this->actingAs($user)->putJson("/api/transaction-categories/{$category->id}", [
        'name' => 'Updated Category Name',
        'sort_order' => 15,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('name', 'Updated Category Name')
        ->assertJsonPath('sort_order', 15);

    $category->refresh();
    expect($category->name)->toBe('Updated Category Name');
    expect($category->sort_order)->toBe(15);
});

test('cannot delete category if transactions exist', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->create();
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    Transaction::factory()->create([
        'account_id' => $account->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/transaction-categories/{$category->id}");

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Cannot delete category with existing transactions',
        ]);

    expect(TransactionCategory::find($category->id))->not->toBeNull();
});

test('can delete category without transactions', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/transaction-categories/{$category->id}");

    $response->assertStatus(204);
    expect(TransactionCategory::find($category->id))->toBeNull();
});
