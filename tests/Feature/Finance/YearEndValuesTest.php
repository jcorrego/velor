<?php

use App\Models\Account;
use App\Models\Asset;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\User;
use App\Models\YearEndValue;
use Livewire\Livewire;

it('updates year-end values for an account from account management', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->for($user)->create([
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2024,
    ]);
    $account = Account::factory()->for($entity)->create([
        'currency_id' => Currency::factory()->euro()->create()->id,
    ]);

    $this->actingAs($user);

    Livewire::test('finance.account-management')
        ->call('openYearEndValues', $account->id)
        ->set('yearEndValues.'.$taxYear->id, '1000')
        ->call('saveYearEndValues')
        ->assertHasNoErrors();

    expect(YearEndValue::query()
        ->where('entity_id', $entity->id)
        ->where('tax_year_id', $taxYear->id)
        ->where('account_id', $account->id)
        ->exists())->toBeTrue();
});

it('removes year-end values when cleared from account management', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->for($user)->create([
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2024,
    ]);
    $account = Account::factory()->for($entity)->create([
        'currency_id' => Currency::factory()->euro()->create()->id,
    ]);

    YearEndValue::create([
        'entity_id' => $entity->id,
        'tax_year_id' => $taxYear->id,
        'account_id' => $account->id,
        'amount' => 1000,
    ]);

    $this->actingAs($user);

    Livewire::test('finance.account-management')
        ->call('openYearEndValues', $account->id)
        ->set('yearEndValues.'.$taxYear->id, '')
        ->call('saveYearEndValues')
        ->assertHasNoErrors();

    expect(YearEndValue::query()
        ->where('account_id', $account->id)
        ->where('tax_year_id', $taxYear->id)
        ->exists())->toBeFalse();
});

it('updates year-end values for an asset from asset management', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->for($user)->create([
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2024,
    ]);
    $asset = Asset::factory()->for($entity)->create();

    $this->actingAs($user);

    Livewire::test('finance.asset-management')
        ->call('openYearEndValues', $asset->id)
        ->set('yearEndValues.'.$taxYear->id, '2500')
        ->call('saveYearEndValues')
        ->assertHasNoErrors();

    expect(YearEndValue::query()
        ->where('asset_id', $asset->id)
        ->where('tax_year_id', $taxYear->id)
        ->exists())->toBeTrue();
});
