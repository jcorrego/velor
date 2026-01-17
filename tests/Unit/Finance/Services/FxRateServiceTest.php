<?php

use App\Finance\Services\FxRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('returns 1.0 for same currency', function () {
    $service = new FxRateService;
    $currency = $this->getCurrency('USD');

    $rate = $service->getRate($currency, $currency, Carbon::parse('2024-01-01'));

    expect($rate)->toBe(1.0);
});

test('returns cached rate if available', function () {
    $service = new FxRateService;
    $eur = $this->getCurrency('EUR');
    $usd = $this->getCurrency('USD');
    $date = Carbon::parse('2024-01-01');

    $cacheKey = 'fx_rate_EUR_USD_2024-01-01';
    Cache::put($cacheKey, 1.15, now()->addDay());

    $rate = $service->getRate($eur, $usd, $date);

    expect($rate)->toBe(1.15);
});

test('returns ECB rate from database', function () {
    $service = new FxRateService;
    $eur = $this->getCurrency('EUR');
    $usd = $this->getCurrency('USD');
    $date = Carbon::parse('2024-01-01');

    // First call will create the rate via fetchAndCacheECBRate
    $firstRate = $service->getRate($eur, $usd, $date);

    // Should return the default rate
    expect($firstRate)->toBe(1.10); // EUR/USD default rate

    // Manually update the rate in the database
    \DB::table('fx_rates')
        ->where('currency_from_id', $eur->id)
        ->where('currency_to_id', $usd->id)
        ->whereDate('rate_date', $date->toDateString())
        ->update(['rate' => 1.12]);

    // Clear cache
    Cache::flush();

    // Second call should return the updated rate from DB
    $secondRate = $service->getRate($eur, $usd, $date);
    expect($secondRate)->toBe(1.12);
});

test('sets override rate and uses it', function () {
    $service = new FxRateService;
    $eur = $this->getCurrency('EUR');
    $usd = $this->getCurrency('USD');
    $date = Carbon::parse('2024-01-01');

    // First, let the service create the initial ECB rate
    $initialRate = $service->getRate($eur, $usd, $date);
    expect($initialRate)->toBe(1.10); // Default EUR/USD rate

    // Now set an override rate
    $service->setOverrideRate($eur, $usd, $date, 1.25);

    // Get the rate again - should return the override
    $rate = $service->getRate($eur, $usd, $date);

    expect($rate)->toBe(1.25);
});

test('converts amount using rate', function () {
    $service = new FxRateService;
    $eur = $this->getCurrency('EUR');
    $usd = $this->getCurrency('USD');
    $date = Carbon::parse('2024-01-01');

    // Let service create the initial rate, then convert
    $service->getRate($eur, $usd, $date); // Creates rate with default 1.10

    $convertedAmount = $service->convert(100.00, $eur, $usd, $date);

    expect($convertedAmount)->toBe(110.00);
});

test('setOverrideRate creates override and invalidates cache', function () {
    $service = new FxRateService;
    $eur = $this->getCurrency('EUR');
    $usd = $this->getCurrency('USD');
    $date = Carbon::parse('2024-01-01');

    $cacheKey = 'fx_rate_EUR_USD_2024-01-01';
    Cache::put($cacheKey, 1.10, now()->addDay());

    $fxRate = $service->setOverrideRate($eur, $usd, $date, 1.30);

    expect($fxRate->source)->toBe('override');
    expect((float) $fxRate->rate)->toBe(1.30);
    expect(Cache::has($cacheKey))->toBeFalse();
});
