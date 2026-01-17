<?php

use App\Models\Entity;
use App\Models\FilingType;
use App\Models\Jurisdiction;
use App\Models\ResidencyPeriod;
use App\Models\TaxYear;
use App\Models\UserProfile;

test('jurisdiction can be created with factory', function () {
    $jurisdiction = Jurisdiction::factory()->create();

    expect($jurisdiction->exists)->toBeTrue()
        ->and($jurisdiction->name)->toBeString()
        ->and($jurisdiction->iso_code)->toBeString()
        ->and($jurisdiction->timezone)->toBeString()
        ->and($jurisdiction->default_currency)->toBeString();
});

test('jurisdiction has many user profiles', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    UserProfile::factory()->count(3)->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($jurisdiction->userProfiles)->toHaveCount(3)
        ->and($jurisdiction->userProfiles->first())->toBeInstanceOf(UserProfile::class);
});

test('jurisdiction has many residency periods', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    ResidencyPeriod::factory()->count(2)->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($jurisdiction->residencyPeriods)->toHaveCount(2)
        ->and($jurisdiction->residencyPeriods->first())->toBeInstanceOf(ResidencyPeriod::class);
});

test('jurisdiction has many tax years', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    TaxYear::factory()->count(3)->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($jurisdiction->taxYears)->toHaveCount(3)
        ->and($jurisdiction->taxYears->first())->toBeInstanceOf(TaxYear::class);
});

test('jurisdiction has many filing types', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    FilingType::factory()->count(3)->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($jurisdiction->filingTypes)->toHaveCount(3)
        ->and($jurisdiction->filingTypes->first())->toBeInstanceOf(FilingType::class);
});

test('jurisdiction has many entities', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    Entity::factory()->count(2)->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($jurisdiction->entities)->toHaveCount(2)
        ->and($jurisdiction->entities->first())->toBeInstanceOf(Entity::class);
});

test('jurisdiction factory has state methods for countries', function () {
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $colombia = Jurisdiction::factory()->colombia()->create();

    expect($spain->iso_code)->toBe('ESP')
        ->and($spain->name)->toBe('Spain')
        ->and($spain->default_currency)->toBe('EUR')
        ->and($usa->iso_code)->toBe('USA')
        ->and($usa->name)->toBe('United States')
        ->and($usa->default_currency)->toBe('USD')
        ->and($colombia->iso_code)->toBe('COL')
        ->and($colombia->name)->toBe('Colombia')
        ->and($colombia->default_currency)->toBe('COP');
});
