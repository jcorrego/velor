<?php

use App\Models\Jurisdiction;
use App\Models\ResidencyPeriod;
use App\Models\User;
use Carbon\CarbonImmutable;

test('residency period can be created with factory', function () {
    $period = ResidencyPeriod::factory()->create();

    expect($period->exists)->toBeTrue()
        ->and($period->user_id)->toBeInt()
        ->and($period->jurisdiction_id)->toBeInt()
        ->and($period->start_date)->toBeInstanceOf(CarbonImmutable::class);
});

test('residency period belongs to user', function () {
    $user = User::factory()->create();
    $period = ResidencyPeriod::factory()->create(['user_id' => $user->id]);

    expect($period->user)->toBeInstanceOf(User::class)
        ->and($period->user->id)->toBe($user->id);
});

test('residency period belongs to jurisdiction', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $period = ResidencyPeriod::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($period->jurisdiction)->toBeInstanceOf(Jurisdiction::class)
        ->and($period->jurisdiction->id)->toBe($jurisdiction->id);
});

test('dates are cast to carbon instances', function () {
    $period = ResidencyPeriod::factory()->create([
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    expect($period->start_date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($period->end_date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($period->start_date->format('Y-m-d'))->toBe('2025-01-01')
        ->and($period->end_date->format('Y-m-d'))->toBe('2025-12-31');
});

test('is_fiscal_residence is cast to boolean', function () {
    $period = ResidencyPeriod::factory()->create(['is_fiscal_residence' => true]);

    expect($period->is_fiscal_residence)->toBeBool()
        ->and($period->is_fiscal_residence)->toBeTrue();
});

test('fiscal residence is determined by 183 day rule', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    // User spent 200 days in Spain
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-07-19', // 200 days
    ]);

    // User spent 165 days in USA
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'start_date' => '2025-07-20',
        'end_date' => '2025-12-31', // 165 days
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->toBeInstanceOf(Jurisdiction::class)
        ->and($fiscalResidence->id)->toBe($spain->id);
});

test('fiscal residence returns null if no jurisdiction has 183 days', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    // User spent 150 days in Spain
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-05-30', // 150 days
    ]);

    // User spent 100 days in USA
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'start_date' => '2025-06-01',
        'end_date' => '2025-09-08', // 100 days
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->toBeNull();
});

test('fiscal residence handles multiple periods in same jurisdiction', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    // User spent two separate periods in Spain totaling 200 days
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-03-31', // 90 days
    ]);

    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-07-01',
        'end_date' => '2025-10-18', // 110 days
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->toBeInstanceOf(Jurisdiction::class)
        ->and($fiscalResidence->id)->toBe($spain->id);
});

test('fiscal residence handles open-ended periods', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    // User moved to Spain on July 1st with no end date
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-07-01',
        'end_date' => null, // Open-ended, still living there
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->toBeInstanceOf(Jurisdiction::class)
        ->and($fiscalResidence->id)->toBe($spain->id);
});
