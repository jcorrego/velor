<?php

use App\Models\Jurisdiction;
use App\Models\ResidencyPeriod;
use App\Models\User;

test('user with 183+ days in one country has fiscal residence there', function () {
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

    expect($fiscalResidence)->not->toBeNull()
        ->and($fiscalResidence->id)->toBe($spain->id)
        ->and($fiscalResidence->iso_code)->toBe('ESP');
});

test('user without 183 days in any country has no fiscal residence', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $colombia = Jurisdiction::factory()->colombia()->create();

    // User split time across three countries
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-04-30', // 120 days
    ]);

    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'start_date' => '2025-05-01',
        'end_date' => '2025-08-31', // 123 days
    ]);

    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $colombia->id,
        'start_date' => '2025-09-01',
        'end_date' => '2025-12-31', // 122 days
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->toBeNull();
});

test('multiple periods in same jurisdiction are aggregated for 183 day rule', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    // User had 3 separate periods in Spain totaling 200 days
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-02-28', // 59 days
    ]);

    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-04-01',
        'end_date' => '2025-05-31', // 61 days
    ]);

    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-08-01',
        'end_date' => '2025-10-19', // 80 days
    ]);

    // User spent remaining time in USA
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-31', // 31 days
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->not->toBeNull()
        ->and($fiscalResidence->id)->toBe($spain->id);
});

test('open-ended residency period counts as fiscal residence', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    // User moved to Spain on July 1st and stayed (184 days)
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-07-01',
        'end_date' => null, // Still living there
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->not->toBeNull()
        ->and($fiscalResidence->id)->toBe($spain->id);
});

test('residency periods spanning multiple years count correctly', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    // User was in Spain from Dec 2024 to Jun 2025
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2024-12-01',
        'end_date' => '2025-06-30', // Only 181 days in 2025
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    // Should not have fiscal residence (only 181 days in 2025)
    expect($fiscalResidence)->toBeNull();
});

test('exactly 183 days qualifies for fiscal residence', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    // User spent exactly 183 days in Spain
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-07-02', // Exactly 183 days
    ]);

    $fiscalResidence = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);

    expect($fiscalResidence)->not->toBeNull()
        ->and($fiscalResidence->id)->toBe($spain->id);
});

test('fiscal residence is determined independently per year', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    // 2025: User was in Spain
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    // 2026: User moved to USA
    ResidencyPeriod::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $fiscalResidence2025 = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2025);
    $fiscalResidence2026 = ResidencyPeriod::getFiscalResidenceForYear($user->id, 2026);

    expect($fiscalResidence2025->id)->toBe($spain->id)
        ->and($fiscalResidence2026->id)->toBe($usa->id);
});
