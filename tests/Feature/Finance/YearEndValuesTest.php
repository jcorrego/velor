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

it('creates a year-end value for an account', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->for($user)->create([
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2024,
    ]);
    $currency = Currency::factory()->euro()->create();
    $account = Account::factory()->for($entity)->create([
        'currency_id' => $currency->id,
    ]);

    $this->actingAs($user);

    Livewire::test('finance.year-end-values')
        ->set('entity_id', (string) $entity->id)
        ->set('tax_year_id', (string) $taxYear->id)
        ->set('valueType', 'account')
        ->set('account_id', (string) $account->id)
        ->set('currency_id', (string) $currency->id)
        ->set('amount', '1000')
        ->set('as_of_date', '2024-12-31')
        ->call('save')
        ->assertHasNoErrors();

    expect(YearEndValue::query()
        ->where('entity_id', $entity->id)
        ->where('tax_year_id', $taxYear->id)
        ->where('account_id', $account->id)
        ->exists())->toBeTrue();
});

it('prevents duplicate year-end values for the same account and tax year', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->for($user)->create([
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2024,
    ]);
    $currency = Currency::factory()->euro()->create();
    $account = Account::factory()->for($entity)->create([
        'currency_id' => $currency->id,
    ]);

    YearEndValue::create([
        'entity_id' => $entity->id,
        'tax_year_id' => $taxYear->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'amount' => 1500,
        'as_of_date' => '2024-12-31',
    ]);

    $this->actingAs($user);

    Livewire::test('finance.year-end-values')
        ->set('entity_id', (string) $entity->id)
        ->set('tax_year_id', (string) $taxYear->id)
        ->set('valueType', 'account')
        ->set('account_id', (string) $account->id)
        ->set('currency_id', (string) $currency->id)
        ->set('amount', '1500')
        ->set('as_of_date', '2024-12-31')
        ->call('save')
        ->assertHasErrors(['account_id' => 'unique']);
});

it('calculates total assets for the selected entity and tax year', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->for($user)->create([
        'jurisdiction_id' => $jurisdiction->id,
    ]);
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2024,
    ]);
    $currency = Currency::factory()->euro()->create();
    $account = Account::factory()->for($entity)->create([
        'currency_id' => $currency->id,
    ]);
    $asset = Asset::factory()->for($entity)->create([
        'jurisdiction_id' => $jurisdiction->id,
        'acquisition_currency_id' => $currency->id,
    ]);

    YearEndValue::create([
        'entity_id' => $entity->id,
        'tax_year_id' => $taxYear->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'amount' => 1000,
        'as_of_date' => '2024-12-31',
    ]);

    YearEndValue::create([
        'entity_id' => $entity->id,
        'tax_year_id' => $taxYear->id,
        'asset_id' => $asset->id,
        'currency_id' => $currency->id,
        'amount' => 2000,
        'as_of_date' => '2024-12-31',
    ]);

    $this->actingAs($user);

    Livewire::test('finance.year-end-values')
        ->set('entity_id', (string) $entity->id)
        ->set('tax_year_id', (string) $taxYear->id)
        ->assertSet('totalAssets', 3000.0);
});
