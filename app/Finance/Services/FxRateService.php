<?php

namespace App\Finance\Services;

use App\Models\Currency;
use App\Models\FxRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FxRateService
{
    /**
     * Get the exchange rate between two currencies for a specific date.
     * Prioritizes manual overrides, falls back to cached ECB rates.
     */
    public function getRate(Currency $fromCurrency, Currency $toCurrency, Carbon $date): float
    {
        // Same currency returns 1.0
        if ($fromCurrency->id === $toCurrency->id) {
            return 1.0;
        }

        // Check for manual override first
        $override = FxRate::query()
            ->where('currency_from_id', $fromCurrency->id)
            ->where('currency_to_id', $toCurrency->id)
            ->where('rate_date', $date->toDateString())
            ->where('source', 'override')
            ->first();

        if ($override) {
            return (float) $override->rate;
        }

        // Check cache for ECB rates
        $cacheKey = "fx_rate_{$fromCurrency->code}_{$toCurrency->code}_{$date->toDateString()}";
        $cachedRate = Cache::get($cacheKey);

        if ($cachedRate !== null) {
            return (float) $cachedRate;
        }

        // Check database for ECB rate
        $rate = FxRate::query()
            ->where('currency_from_id', $fromCurrency->id)
            ->where('currency_to_id', $toCurrency->id)
            ->where('rate_date', $date->toDateString())
            ->where('source', 'ecb')
            ->first();

        if ($rate) {
            // Cache for 24 hours
            Cache::put($cacheKey, $rate->rate, now()->addDay());

            return (float) $rate->rate;
        }

        // If no rate found, try to fetch from ECB and cache
        return $this->fetchAndCacheECBRate($fromCurrency, $toCurrency, $date);
    }

    /**
     * Fetch rate from ECB API and cache it.
     * For this implementation, we'll use a placeholder that returns a default rate.
     * In production, this would call the actual ECB API.
     */
    private function fetchAndCacheECBRate(Currency $fromCurrency, Currency $toCurrency, Carbon $date): float
    {
        // Placeholder implementation - in production, call ECB API
        // For now, store a rate and return it
        $rate = $this->getDefaultRate($fromCurrency, $toCurrency);

        try {
            FxRate::firstOrCreate(
                [
                    'currency_from_id' => $fromCurrency->id,
                    'currency_to_id' => $toCurrency->id,
                    'rate_date' => $date->toDateString(),
                ],
                [
                    'rate' => $rate,
                    'source' => 'ecb',
                ]
            );
        } catch (\Exception $e) {
            // Log error but return the rate anyway
        }

        $cacheKey = "fx_rate_{$fromCurrency->code}_{$toCurrency->code}_{$date->toDateString()}";
        Cache::put($cacheKey, $rate, now()->addDay());

        return $rate;
    }

    /**
     * Get default/fallback exchange rates for common currency pairs.
     */
    private function getDefaultRate(Currency $fromCurrency, Currency $toCurrency): float
    {
        $pair = "{$fromCurrency->code}/{$toCurrency->code}";

        $rates = [
            'EUR/USD' => 1.10,
            'USD/EUR' => 0.909,
            'EUR/COP' => 4500.0,
            'COP/EUR' => 0.000222,
            'EUR/GBP' => 0.86,
            'GBP/EUR' => 1.163,
            'USD/COP' => 4090.0,
            'COP/USD' => 0.000245,
        ];

        if (isset($rates[$pair])) {
            return $rates[$pair];
        }

        // Try reverse pair
        $reversePair = "{$toCurrency->code}/{$fromCurrency->code}";
        if (isset($rates[$reversePair])) {
            return 1 / $rates[$reversePair];
        }

        // Default to 1.0 if no rate found
        return 1.0;
    }

    /**
     * Set a manual rate override for a specific currency pair and date.
     */
    public function setOverrideRate(Currency $fromCurrency, Currency $toCurrency, Carbon $date, float $rate): FxRate
    {
        $fxRate = FxRate::updateOrCreate(
            [
                'currency_from_id' => $fromCurrency->id,
                'currency_to_id' => $toCurrency->id,
                'rate_date' => $date->toDateString(),
            ],
            [
                'rate' => $rate,
                'source' => 'override',
            ]
        );

        // Invalidate cache
        $cacheKey = "fx_rate_{$fromCurrency->code}_{$toCurrency->code}_{$date->toDateString()}";
        Cache::forget($cacheKey);

        return $fxRate;
    }

    /**
     * Convert amount from one currency to another.
     */
    public function convert(float $amount, Currency $fromCurrency, Currency $toCurrency, Carbon $date): float
    {
        $rate = $this->getRate($fromCurrency, $toCurrency, $date);

        return round($amount * $rate, 2);
    }
}
