<?php

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates an address from management ui', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('management.addresses')
        ->set('address_line_1', '123 Market Street')
        ->set('address_line_2', 'Suite 400')
        ->set('city', 'San Francisco')
        ->set('state', 'CA')
        ->set('postal_code', '94103')
        ->set('country', 'United States')
        ->call('save')
        ->assertHasNoErrors();

    expect(Address::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('updates an existing address from management ui', function () {
    $user = User::factory()->create();

    $address = Address::factory()->create([
        'user_id' => $user->id,
        'address_line_1' => '123 Market Street',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postal_code' => '94103',
        'country' => 'United States',
    ]);

    Livewire::actingAs($user)
        ->test('management.addresses')
        ->call('edit', $address->id)
        ->set('city', 'Oakland')
        ->call('save')
        ->assertHasNoErrors();

    $address->refresh();
    expect($address->city)->toBe('Oakland');
});
