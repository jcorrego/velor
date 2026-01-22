<?php

use App\Enums\Finance\AssetType;
use App\Enums\Finance\OwnershipStructure;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('list assets with pagination', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);

    Asset::factory()
        ->count(20)
        ->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->getJson('/api/assets');

    $response->assertSuccessful()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.per_page', 15);
});

test('create residential asset with depreciation', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $jurisdiction = Jurisdiction::factory()->create();
    $currency = $this->getCurrency('USD');

    $data = [
        'name' => 'Residential Property Downtown',
        'type' => AssetType::Residential->value,
        'jurisdiction_id' => $jurisdiction->id,
        'entity_id' => $entity->id,
        'ownership_structure' => OwnershipStructure::Direct->value,
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 250000.00,
        'acquisition_currency_id' => $currency->id,
        'depreciation_method' => 'straight-line',
        'useful_life_years' => 28,
        'annual_depreciation_amount' => 8928.57,
    ];

    $response = $this->actingAs($user)->postJson('/api/assets', $data);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'Residential Property Downtown')
        ->assertJsonPath('type', AssetType::Residential->value)
        ->assertJsonPath('annual_depreciation_amount', '8928.57');
});

test('validation fails with invalid asset type', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $jurisdiction = Jurisdiction::factory()->create();
    $currency = $this->getCurrency('USD');

    $data = [
        'name' => 'Test Asset',
        'type' => 'invalid_asset_type',
        'jurisdiction_id' => $jurisdiction->id,
        'entity_id' => $entity->id,
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000.00,
        'acquisition_currency_id' => $currency->id,
    ];

    $response = $this->actingAs($user)->postJson('/api/assets', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

test('create vehicle asset', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $jurisdiction = Jurisdiction::factory()->create();
    $currency = $this->getCurrency('EUR');

    $data = [
        'name' => 'Peugot 5008',
        'type' => AssetType::Vehicle->value,
        'jurisdiction_id' => $jurisdiction->id,
        'entity_id' => $entity->id,
        'ownership_structure' => OwnershipStructure::Direct->value,
        'acquisition_date' => '2021-03-15',
        'acquisition_cost' => 32000.00,
        'acquisition_currency_id' => $currency->id,
    ];

    $response = $this->actingAs($user)->postJson('/api/assets', $data);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'Peugot 5008')
        ->assertJsonPath('type', AssetType::Vehicle->value);
});

test('view asset details', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $asset = Asset::factory()->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->getJson("/api/assets/{$asset->id}");

    $response->assertSuccessful()
        ->assertJsonPath('id', $asset->id)
        ->assertJsonStructure([
            'id',
            'name',
            'type',
        ]);
});

test('update asset ownership_structure and depreciation', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $asset = Asset::factory()->create([
        'entity_id' => $entity->id,
        'ownership_structure' => OwnershipStructure::Direct->value,
        'annual_depreciation_amount' => 5000.00,
    ]);

    $response = $this->actingAs($user)->putJson("/api/assets/{$asset->id}", [
        'ownership_structure' => OwnershipStructure::Corporation->value,
        'annual_depreciation_amount' => 7500.00,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('ownership_structure', OwnershipStructure::Corporation->value)
        ->assertJsonPath('annual_depreciation_amount', '7500.00');

    $asset->refresh();
    expect($asset->ownership_structure)->toBe(OwnershipStructure::Corporation);
    expect((float) $asset->annual_depreciation_amount)->toBe(7500.00);
});

test('cannot change acquisition_date', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $asset = Asset::factory()->create([
        'entity_id' => $entity->id,
        'acquisition_date' => '2020-01-01',
    ]);

    $response = $this->actingAs($user)->putJson("/api/assets/{$asset->id}", [
        'acquisition_date' => '2021-01-01',
    ]);

    $asset->refresh();
    expect($asset->acquisition_date->format('Y-m-d'))->toBe('2020-01-01');
});

test('delete asset', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $asset = Asset::factory()->create(['entity_id' => $entity->id]);

    $response = $this->actingAs($user)->deleteJson("/api/assets/{$asset->id}");

    $response->assertStatus(204);
    expect(Asset::find($asset->id))->toBeNull();
});
