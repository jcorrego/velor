<?php

use App\EntityType;
use App\FilingStatus;
use App\Livewire\Management\Entities;
use App\Livewire\Management\Filings;
use App\Livewire\Management\Profiles;
use App\Livewire\Management\ResidencyPeriods;
use App\Models\Entity;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\Jurisdiction;
use App\Models\ResidencyPeriod;
use App\Models\TaxYear;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can create a jurisdiction profile from the management ui', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->usa()->create([
        'iso_code' => 'USA',
    ]);

    Livewire::actingAs($user)
        ->test(Profiles::class)
        ->set('jurisdiction_id', $jurisdiction->id)
        ->set('name', 'John Correa')
        ->set('tax_id', '123-45-6789')
        ->set('default_currency', 'USD')
        ->set('display_currency', 'USD')
        ->call('save');

    $profile = UserProfile::query()->where('user_id', $user->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->jurisdiction_id)->toBe($jurisdiction->id)
        ->and($profile->display_currencies['USA'])->toBe('USD');
});

test('user can add a residency period from the management ui', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->spain()->create();

    Livewire::actingAs($user)
        ->test(ResidencyPeriods::class)
        ->set('jurisdiction_id', $jurisdiction->id)
        ->set('start_date', '2025-01-01')
        ->set('end_date', '2025-06-30')
        ->call('save');

    $period = ResidencyPeriod::query()->where('user_id', $user->id)->first();

    expect($period)->not->toBeNull()
        ->and($period->jurisdiction_id)->toBe($jurisdiction->id)
        ->and($period->start_date->format('Y-m-d'))->toBe('2025-01-01');
});

test('user can create an entity from the management ui', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->colombia()->create();

    Livewire::actingAs($user)
        ->test(Entities::class)
        ->set('jurisdiction_id', $jurisdiction->id)
        ->set('type', EntityType::LLC->value)
        ->set('name', 'Velor Holdings LLC')
        ->call('save');

    $entity = Entity::query()->where('user_id', $user->id)->first();

    expect($entity)->not->toBeNull()
        ->and($entity->jurisdiction_id)->toBe($jurisdiction->id)
        ->and($entity->type)->toBe(EntityType::LLC);
});

test('user can update a filing status from the management ui', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->usa()->create();
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2025,
    ]);
    $filingType = FilingType::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'code' => '5472',
        'name' => 'Form 5472',
    ]);
    $filing = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'status' => FilingStatus::Planning,
    ]);

    Livewire::actingAs($user)
        ->test(Filings::class)
        ->call('edit', $filing->id)
        ->set('status', FilingStatus::Filed->value)
        ->call('save');

    expect($filing->fresh()->status)->toBe(FilingStatus::Filed);
});
