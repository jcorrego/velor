<?php

use App\Models\Currency;
use App\Models\FxRate;
use App\Models\Transaction;
use App\Services\Finance\FxRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

it('retrieves exchange rate for currency pair', function () {
    $usd = Currency::factory()->create(['code' => 'USD']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    FxRate::factory()->create([
        'currency_from_id' => $usd->id,
        'currency_to_id' => $eur->id,
        'rate' => 0.92,
        'rate_date' => '2025-01-16',
    ]);

    $service = new FxRateService;
    $rate = $service->getRate('USD', 'EUR', Carbon::parse('2025-01-16'));

    expect($rate)->toBe(0.92);
});

it('returns 1.0 for same currency conversion', function () {
    $service = new FxRateService;
    $rate = $service->getRate('USD', 'USD');

    expect($rate)->toBe(1.0);
});

it('returns null for missing currency', function () {
    Currency::factory()->create(['code' => 'USD']);

    $service = new FxRateService;
    $rate = $service->getRate('USD', 'XYZ');

    expect($rate)->toBeNull();
});

it('converts amount using stored rate', function () {
    $usd = Currency::factory()->create(['code' => 'USD']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    FxRate::factory()->create([
        'currency_from_id' => $usd->id,
        'currency_to_id' => $eur->id,
        'rate' => 0.92,
        'rate_date' => '2025-01-16',
    ]);

    $service = new FxRateService;
    $converted = $service->convertAmount(100, 'USD', 'EUR', Carbon::parse('2025-01-16'));

    expect($converted)->toBe(92.0);
});

it('uses inverse rate if direct rate not found', function () {
    $usd = Currency::factory()->create(['code' => 'USD']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    // Store only EUR to USD rate
    FxRate::factory()->create([
        'currency_from_id' => $eur->id,
        'currency_to_id' => $usd->id,
        'rate' => 1.0870,
        'rate_date' => '2025-01-16',
    ]);

    $service = new FxRateService;
    $rate = $service->getRate('USD', 'EUR', Carbon::parse('2025-01-16'));

    // Should calculate as 1 / 1.0870 ≈ 0.92
    expect($rate)->toBeGreaterThan(0.919)->toBeLessThan(0.921);
});

it('allows overriding exchange rate', function () {
    $usd = Currency::factory()->create(['code' => 'USD']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    $service = new FxRateService;
    $service->overrideRate('USD', 'EUR', 0.95, Carbon::parse('2025-01-16'), 'override');

    $rate = $service->getRate('USD', 'EUR', Carbon::parse('2025-01-16'));

    expect($rate)->toBe(0.95);
});

it('retrieves latest rate when no specific date rate exists', function () {
    $usd = Currency::factory()->create(['code' => 'USD']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    FxRate::factory()->create([
        'currency_from_id' => $usd->id,
        'currency_to_id' => $eur->id,
        'rate' => 0.92,
        'rate_date' => '2025-01-15',
    ]);

    $service = new FxRateService;
    $latestRate = $service->getLatestRate('USD', 'EUR');

    expect($latestRate)->not->toBeNull();
    expect((float) $latestRate->rate)->toBe(0.92);
});

it('caches retrieved rates', function () {
    $usd = Currency::factory()->create(['code' => 'USD']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    FxRate::factory()->create([
        'currency_from_id' => $usd->id,
        'currency_to_id' => $eur->id,
        'rate' => 0.92,
        'rate_date' => '2025-01-16',
    ]);

    $service = new FxRateService;
    $rate1 = $service->getRate('USD', 'EUR', Carbon::parse('2025-01-16'));
    $rate2 = $service->getRate('USD', 'EUR', Carbon::parse('2025-01-16'));

    expect($rate1)->toBe($rate2);
});

it('fetches and stores ECB rates for requested pair', function () {
    Currency::factory()->create(['code' => 'USD']);
    Currency::factory()->create(['code' => 'EUR']);

    Http::fake([
        'www.ecb.europa.eu/*' => Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
    <Cube>
        <Cube time="2024-01-15">
            <Cube currency="USD" rate="1.2000"/>
            <Cube currency="GBP" rate="0.8000"/>
        </Cube>
    </Cube>
</gesmes:Envelope>
XML),
    ]);

    $service = new FxRateService;
    $rate = $service->fetchRate('USD', 'EUR', Carbon::parse('2024-01-15'));

    expect($rate)->not->toBeNull()
        ->and((float) $rate->rate)->toBeGreaterThan(0.8332)->toBeLessThan(0.8334)
        ->and($rate->source)->toBe('ecb');
});

it('computes cross rates from ECB data', function () {
    Currency::factory()->create(['code' => 'USD']);
    Currency::factory()->create(['code' => 'GBP']);

    Http::fake([
        'www.ecb.europa.eu/*' => Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
    <Cube>
        <Cube time="2024-01-15">
            <Cube currency="USD" rate="1.2000"/>
            <Cube currency="GBP" rate="0.8000"/>
        </Cube>
    </Cube>
</gesmes:Envelope>
XML),
    ]);

    $service = new FxRateService;
    $rate = $service->fetchRate('USD', 'GBP', Carbon::parse('2024-01-15'));

    expect($rate)->not->toBeNull()
        ->and((float) $rate->rate)->toBeGreaterThan(0.6665)->toBeLessThan(0.6667);
});

it('overrides FX rate for a transaction', function () {
    $usd = Currency::factory()->create(['code' => 'USD']);
    $eur = Currency::factory()->create(['code' => 'EUR']);

    $transaction = Transaction::factory()->create([
        'original_amount' => 1000,
        'original_currency_id' => $usd->id,
        'converted_currency_id' => $eur->id,
        'fx_rate' => 0.9,
        'converted_amount' => 900,
        'fx_source' => 'ecb',
    ]);

    $service = new FxRateService;
    $service->overrideRateForTransaction($transaction->id, 0.95, 'bank_rate');

    $transaction->refresh();

    expect((float) $transaction->fx_rate)->toBe(0.95)
        ->and((float) $transaction->converted_amount)->toBe(950.0)
        ->and($transaction->fx_source)->toBe('bank_rate');
});
