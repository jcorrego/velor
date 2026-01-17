<?php

use App\Jobs\SyncEcbExchangeRates;
use App\Models\Currency;
use App\Models\FxRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('syncs ECB exchange rates successfully', function () {
    Log::spy();
    Http::fake([
        'www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml' => Http::response(
            file_get_contents(base_path('tests/fixtures/ecb-rates.xml')),
            200
        ),
    ]);

    // Create base currencies
    $eur = Currency::factory()->create(['code' => 'EUR']);
    $usd = Currency::factory()->create(['code' => 'USD']);
    $cop = Currency::factory()->create(['code' => 'COP']);

    dispatch_sync(new SyncEcbExchangeRates);

    // Verify rates were stored
    expect(FxRate::count())->toBeGreaterThan(0);
    expect(FxRate::where('currency_from_id', $usd->id)->where('currency_to_id', $eur->id)->exists())->toBeTrue();
});

it('handles ECB API errors gracefully', function () {
    Log::spy();
    Http::fake([
        'www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml' => Http::response(
            'Error',
            500
        ),
    ]);

    $job = new SyncEcbExchangeRates;
    $job->handle();

    Log::shouldHaveReceived('error');
    Log::shouldHaveReceived('warning');
});

it('skips storing rates for inactive currencies', function () {
    Http::fake([
        'www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml' => Http::response(
            file_get_contents(base_path('tests/fixtures/ecb-rates.xml')),
            200
        ),
    ]);

    // Create only EUR as active
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);
    Currency::factory()->create(['code' => 'USD', 'is_active' => false]);

    dispatch_sync(new SyncEcbExchangeRates);

    // USD rate should not be stored since it's inactive
    expect(FxRate::count())->toBe(0);
});

it('updates existing rates instead of creating duplicates', function () {
    Http::fake([
        'www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml' => Http::response(
            file_get_contents(base_path('tests/fixtures/ecb-rates.xml')),
            200
        ),
    ]);

    Currency::factory()->create(['code' => 'EUR']);
    Currency::factory()->create(['code' => 'USD']);

    // Run sync twice
    dispatch_sync(new SyncEcbExchangeRates);
    $countAfterFirst = FxRate::count();

    dispatch_sync(new SyncEcbExchangeRates);
    $countAfterSecond = FxRate::count();

    // Count should not increase significantly on second sync
    expect($countAfterSecond)->toBeLessThanOrEqual($countAfterFirst);
});
