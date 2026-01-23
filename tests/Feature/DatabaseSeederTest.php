<?php

use App\Models\Account;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\YearEndValue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder creates mercury year end value', function () {
    $this->seed(DatabaseSeeder::class);

    $account = Account::query()
        ->where('name', 'Mercury USD Account')
        ->first();

    $usaJurisdiction = Jurisdiction::query()
        ->where('iso_code', 'USA')
        ->first();

    $taxYear = TaxYear::query()
        ->where('year', 2025)
        ->where('jurisdiction_id', $usaJurisdiction?->id)
        ->first();

    expect($account)->not->toBeNull();
    expect($taxYear)->not->toBeNull();

    $exists = YearEndValue::query()
        ->where('account_id', $account->id)
        ->where('tax_year_id', $taxYear->id)
        ->where('amount', 14647.49)
        ->exists();

    expect($exists)->toBeTrue();
});
