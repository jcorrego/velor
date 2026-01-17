<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('list categories', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);

    TransactionCategory::factory()
        ->count(10)
        ->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->getJson('/api/transaction-categories');

    $response->assertSuccessful()
        ->assertJsonCount(10, 'data');
});

test('filter by jurisdiction_id', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $jurisdiction1 = Jurisdiction::factory()->create();
    $jurisdiction2 = Jurisdiction::factory()->create();

    TransactionCategory::factory()
        ->count(5)
        ->create([
            'entity_id' => $entity->id,
            'jurisdiction_id' => $jurisdiction1->id,
        ]);

    TransactionCategory::factory()
        ->count(3)
        ->create([
            'entity_id' => $entity->id,
            'jurisdiction_id' => $jurisdiction2->id,
        ]);

    $response = $this->actingAs($user)->getJson("/api/transaction-categories?jurisdiction_id={$jurisdiction1->id}");

    $response->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

test('create category with valid jurisdiction and entity', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $jurisdiction = Jurisdiction::factory()->create();

    $data = [
        'name' => 'Business Expenses',
        'jurisdiction_id' => $jurisdiction->id,
        'entity_id' => $entity->id,
        'income_or_expense' => 'expense',
        'sort_order' => 10,
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction-categories', $data);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'Business Expenses')
        ->assertJsonPath('income_or_expense', 'expense')
        ->assertJsonPath('sort_order', 10);
});

test('validation fails with duplicate name for same jurisdiction entity', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $jurisdiction = Jurisdiction::factory()->create();

    TransactionCategory::factory()->create([
        'name' => 'Rental Income',
        'jurisdiction_id' => $jurisdiction->id,
        'entity_id' => $entity->id,
    ]);

    $data = [
        'name' => 'Rental Income',
        'jurisdiction_id' => $jurisdiction->id,
        'entity_id' => $entity->id,
        'income_or_expense' => 'income',
    ];

    $response = $this->actingAs($user)->postJson('/api/transaction-categories', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('view category with tax mappings', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->getJson("/api/transaction-categories/{$category->id}");

    $response->assertSuccessful()
        ->assertJsonPath('id', $category->id)
        ->assertJsonPath('name', $category->name)
        ->assertJsonStructure([
            'id',
            'name',
            'jurisdiction_id',
            'entity_id',
            'taxMappings',
        ]);
});

test('update category', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->create([
        'entity_id' => $entity->id,
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
    $category = TransactionCategory::factory()->create(['entity_id' => $entity->id]);
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
    $category = TransactionCategory::factory()->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->deleteJson("/api/transaction-categories/{$category->id}");

    $response->assertStatus(204);
    expect(TransactionCategory::find($category->id))->toBeNull();
});
