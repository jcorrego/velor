<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a currency via the management screen', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('management.currencies')
        ->set('code', 'gbp')
        ->set('name', 'British Pound')
        ->set('symbol', 'GBP')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $currency = Currency::query()->where('code', 'GBP')->first();

    expect($currency)->not->toBeNull()
        ->and($currency->name)->toBe('British Pound')
        ->and($currency->symbol)->toBe('GBP')
        ->and($currency->is_active)->toBeTrue();
});

it('prevents duplicate currency codes', function () {
    $user = User::factory()->create();

    Currency::factory()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
    ]);

    $this->actingAs($user);

    Livewire::test('management.currencies')
        ->set('code', 'USD')
        ->set('name', 'Duplicate Dollar')
        ->set('is_active', true)
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

it('disables a currency from the management screen', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->create([
        'code' => 'CHF',
        'name' => 'Swiss Franc',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('management.currencies')
        ->call('disable', $currency->id);

    expect($currency->fresh()->is_active)->toBeFalse();
});

it('deletes a currency from the management screen', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->create([
        'code' => 'JPY',
        'name' => 'Japanese Yen',
    ]);

    $this->actingAs($user);

    Livewire::test('management.currencies')
        ->call('delete', $currency->id);

    expect(Currency::query()->find($currency->id))->toBeNull();
});

it('prevents disabling a currency that is in use', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->create([
        'code' => 'SEK',
        'name' => 'Swedish Krona',
        'is_active' => true,
    ]);
    $entity = Entity::factory()->create([
        'user_id' => $user->id,
    ]);

    Account::factory()->create([
        'entity_id' => $entity->id,
        'currency_id' => $currency->id,
    ]);

    $this->actingAs($user);

    Livewire::test('management.currencies')
        ->call('disable', $currency->id)
        ->assertHasErrors(['currency']);

    expect($currency->fresh()->is_active)->toBeTrue();
});

it('prevents deleting a currency that is in use', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->create([
        'code' => 'NOK',
        'name' => 'Norwegian Krone',
    ]);
    $entity = Entity::factory()->create([
        'user_id' => $user->id,
    ]);

    Account::factory()->create([
        'entity_id' => $entity->id,
        'currency_id' => $currency->id,
    ]);

    $this->actingAs($user);

    Livewire::test('management.currencies')
        ->call('delete', $currency->id)
        ->assertHasErrors(['currency']);

    expect(Currency::query()->find($currency->id))->not->toBeNull();
});

it('disables destructive buttons for currencies in use', function () {
    $user = User::factory()->create();
    $currency = Currency::factory()->create([
        'code' => 'CAD',
        'name' => 'Canadian Dollar',
        'is_active' => true,
    ]);
    $entity = Entity::factory()->create([
        'user_id' => $user->id,
    ]);

    Account::factory()->create([
        'entity_id' => $entity->id,
        'currency_id' => $currency->id,
    ]);

    $this->actingAs($user);

    Livewire::test('management.currencies')
        ->assertSeeHtml('data-in-use="true"')
        ->assertSeeHtml('data-action="disable"')
        ->assertSeeHtml('data-action="delete"')
        ->assertSeeHtml('data-disabled="true"');
});
