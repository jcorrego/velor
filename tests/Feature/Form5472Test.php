<?php

declare(strict_types=1);

use App\Enums\Finance\TaxFormCode;
use App\Enums\Finance\TransactionType;
use App\Models\Account;
use App\Models\CategoryTaxMapping;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('auto-fills reporting corporation name and EIN from first USA entity', function () {
    $user = User::factory()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $taxYear = TaxYear::factory()->for($usa)->create(['year' => 2025]);

    Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
    );

    $entity = Entity::factory()->for($user)->for($usa)->create([
        'name' => 'Acme USA LLC',
        'ein_or_tax_id' => '12-3456789',
    ]);

    $filingType = FilingType::factory()->for($usa)->create([
        'code' => '5472',
        'name' => 'Form 5472',
    ]);

    $filing = Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingType)
        ->create();

    Livewire::actingAs($user)
        ->test('finance.form-5472')
        ->set('filingId', (string) $filing->id)
        ->assertSet('formData.1a', $entity->name)
        ->assertSet('formData.1b', $entity->ein_or_tax_id);
});

it('renders form 5472 data entry and saves supplemental data', function () {
    $user = User::factory()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $taxYear = TaxYear::factory()->for($usa)->create(['year' => 2025]);

    Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
    );

    $filingType = FilingType::factory()->for($usa)->create([
        'code' => '5472',
        'name' => 'Form 5472',
    ]);

    $filing = Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingType)
        ->create();

    Livewire::actingAs($user)
        ->test('finance.form-5472')
        ->set('filingId', (string) $filing->id)
        ->assertSee('Owner Contributions')
        ->set('formData.reporting_corp_name', 'JCO Services LLC')
        ->set('formData.reporting_corp_ein', '12-3456789')
        ->set('formData.type_of_filer', 'foreign_owned_us_corporation')
        ->set('formData.final_amended_return', true)
        ->set('formData.shareholder_name', 'Acme Holdings')
        ->assertHasNoErrors();

    $fresh = $filing->fresh();

    expect($fresh->form_data['shareholder_name'])->toBe('Acme Holdings');
});

it('renders calculated fields for mapped line items', function () {
    $user = User::factory()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $taxYear = TaxYear::factory()->for($usa)->create(['year' => 2025]);

    $filingType = FilingType::factory()->for($usa)->create([
        'code' => '5472',
        'name' => 'Form 5472',
    ]);

    $filing = Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingType)
        ->create();

    $entity = Entity::factory()->for($user)->for($usa)->create();
    $account = Account::factory()->for($entity)->create();
    $usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
    );

    $categoryOne = TransactionCategory::factory()->create();
    $categoryTwo = TransactionCategory::factory()->create();

    CategoryTaxMapping::factory()->create([
        'category_id' => $categoryOne->id,
        'tax_form_code' => TaxFormCode::Form5472,
        'line_item' => '35',
        'country' => 'USA',
    ]);

    CategoryTaxMapping::factory()->create([
        'category_id' => $categoryTwo->id,
        'tax_form_code' => TaxFormCode::Form5472,
        'line_item' => '35',
        'country' => 'USA',
    ]);

    Transaction::factory()->forAccount($account)->create([
        'transaction_date' => '2025-02-01',
        'type' => TransactionType::Income,
        'original_amount' => 100.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 100.00,
        'converted_currency_id' => $usd->id,
        'fx_rate' => 1.0,
        'category_id' => $categoryOne->id,
    ]);

    Transaction::factory()->forAccount($account)->create([
        'transaction_date' => '2025-03-01',
        'type' => TransactionType::Income,
        'original_amount' => 250.00,
        'original_currency_id' => $usd->id,
        'converted_amount' => 250.00,
        'converted_currency_id' => $usd->id,
        'fx_rate' => 1.0,
        'category_id' => $categoryTwo->id,
    ]);

    Livewire::actingAs($user)
        ->test('finance.form-5472')
        ->set('filingId', (string) $filing->id)
        ->assertSee('$350.00')
        ->assertSee('Calculated from 2 transactions across 2 categories.');
});

test('currency field renders with proper mask and handles data binding', function () {
    $user = User::factory()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $taxYear = TaxYear::factory()->for($usa)->create(['year' => 2025]);

    // Ensure USD currency exists
    Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
    );

    $entity = Entity::factory()->for($user)->for($usa)->create([
        'name' => 'Test Entity',
        'ein_or_tax_id' => '12-3456789',
    ]);

    $filingType = FilingType::factory()->for($usa)->create([
        'code' => '5472',
        'name' => 'Form 5472',
    ]);

    $filing = Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingType)
        ->create();

    Livewire::actingAs($user)
        ->test('finance.form-5472')
        ->set('filingId', (string) $filing->id)
        ->assertSet('formData.1c', '') // Currency field should be empty initially
        ->set('formData.1c', '1234567.89') // Test setting currency value
        ->assertSet('formData.1c', '1234567.89'); // Data binding should work correctly
});
