<?php

use App\Enums\Finance\AccountType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list their accounts', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $currency = Currency::factory()->create();

    Account::factory()
        ->count(3)
        ->create([
            'entity_id' => $entity->id,
            'currency_id' => $currency->id,
        ]);

    $response = $this->actingAs($user)->getJson('/api/accounts');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

test('unauthenticated user gets 401', function () {
    $response = $this->getJson('/api/accounts');

    $response->assertStatus(401);
});

test('user cannot see other users accounts', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $entity1 = Entity::factory()->create(['user_id' => $user1->id]);
    $entity2 = Entity::factory()->create(['user_id' => $user2->id]);

    Account::factory()->create(['entity_id' => $entity1->id]);
    Account::factory()->count(2)->create(['entity_id' => $entity2->id]);

    $response = $this->actingAs($user1)->getJson('/api/accounts');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

test('authenticated user can create account with valid data', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $currency = Currency::factory()->create();

    $data = [
        'name' => 'Test Bank Account',
        'type' => AccountType::Checking->value,
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
        'opening_date' => '2024-01-01',
    ];

    $response = $this->actingAs($user)->postJson('/api/accounts', $data);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'Test Bank Account')
        ->assertJsonPath('type', AccountType::Checking->value);
});

test('validation fails with missing required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/accounts', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'type', 'currency_id', 'entity_id', 'opening_date']);
});

test('validation fails with invalid account type', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $currency = Currency::factory()->create();

    $data = [
        'name' => 'Test Account',
        'type' => 'invalid_type',
        'currency_id' => $currency->id,
        'entity_id' => $entity->id,
        'opening_date' => '2024-01-01',
    ];

    $response = $this->actingAs($user)->postJson('/api/accounts', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

test('authenticated user can view their account', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->getJson("/api/accounts/{$account->id}");

    $response->assertSuccessful()
        ->assertJsonPath('id', $account->id)
        ->assertJsonPath('name', $account->name);
});

test('user cannot view other users account', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $entity2 = Entity::factory()->create(['user_id' => $user2->id]);
    $account = Account::factory()->create(['entity_id' => $entity2->id]);

    $response = $this->actingAs($user1)->getJson("/api/accounts/{$account->id}");

    $response->assertStatus(403);
});

test('authenticated user can update their account', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create([
        'entity_id' => $entity->id,
        'name' => 'Old Name',
    ]);

    $response = $this->actingAs($user)->putJson("/api/accounts/{$account->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('name', 'Updated Name');

    $account->refresh();
    expect($account->name)->toBe('Updated Name');
});

test('cannot change opening_date on update', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create([
        'entity_id' => $entity->id,
        'opening_date' => '2020-01-01',
    ]);

    $response = $this->actingAs($user)->putJson("/api/accounts/{$account->id}", [
        'opening_date' => '2021-01-01',
    ]);

    $account->refresh();
    expect($account->opening_date->format('Y-m-d'))->toBe('2020-01-01');
});

test('authenticated user can delete their account', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->deleteJson("/api/accounts/{$account->id}");

    $response->assertStatus(204);
    expect(Account::find($account->id))->toBeNull();
});

test('user cannot delete other users account', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $entity2 = Entity::factory()->create(['user_id' => $user2->id]);
    $account = Account::factory()->create(['entity_id' => $entity2->id]);

    $response = $this->actingAs($user1)->deleteJson("/api/accounts/{$account->id}");

    $response->assertStatus(403);
    expect(Account::find($account->id))->not->toBeNull();
});
