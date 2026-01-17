<?php

namespace App\Services\Finance;

use App\Models\Currency;
use App\Models\FxRate;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FxRateService
{
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the exchange rate from one currency to another on a specific date.
     *
     * @return float|null The exchange rate, or null if not found
     */
    public function getRate(string $fromCode, string $toCode, ?Carbon $date = null): ?float
    {
        if ($fromCode === $toCode) {
            return 1.0;
        }

        $date ??= now();

        // Try cache first
        $cacheKey = "fx_rate:{$fromCode}:{$toCode}:{$date->toDateString()}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Query database
        $fromCurrency = Currency::whereCode($fromCode)->first();
        $toCurrency = Currency::whereCode($toCode)->first();

        if (! $fromCurrency || ! $toCurrency) {
            return null;
        }

        $rate = FxRate::where('currency_from_id', $fromCurrency->id)
            ->where('currency_to_id', $toCurrency->id)
            ->whereDate('rate_date', $date)
            ->orderByDesc('created_at')
            ->first();

        if ($rate) {
            Cache::put($cacheKey, $rate->rate, self::CACHE_TTL);

            return (float) $rate->rate;
        }

        // If not found, try looking for the inverse rate
        $inverseRate = FxRate::where('currency_from_id', $toCurrency->id)
            ->where('currency_to_id', $fromCurrency->id)
            ->whereDate('rate_date', $date)
            ->orderByDesc('created_at')
            ->first();

        if ($inverseRate) {
            $calculatedRate = 1.0 / (float) $inverseRate->rate;
            Cache::put($cacheKey, $calculatedRate, self::CACHE_TTL);

            return $calculatedRate;
        }

        $fetchedRate = $this->fetchRate($fromCode, $toCode, $date);

        if (! $fetchedRate) {
            return null;
        }

        Cache::put($cacheKey, $fetchedRate->rate, self::CACHE_TTL);

        return (float) $fetchedRate->rate;
    }

    /**
     * Convert an amount from one currency to another on a specific date.
     *
     * @return float|null The converted amount, or null if rate not found
     */
    public function convertAmount(
        float $amount,
        string $fromCode,
        string $toCode,
        ?Carbon $date = null,
    ): ?float {
        if ($fromCode === $toCode) {
            return $amount;
        }

        $rate = $this->getRate($fromCode, $toCode, $date);

        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    /**
     * Override the exchange rate for a specific transaction date.
     *
     * Creates or updates an FxRate record with a manual override.
     */
    public function overrideRate(
        string $fromCode,
        string $toCode,
        float $rate,
        Carbon $date,
        string $reason = 'override',
    ): FxRate {
        $fromCurrency = Currency::whereCode($fromCode)->firstOrFail();
        $toCurrency = Currency::whereCode($toCode)->firstOrFail();

        $fxRate = FxRate::where('currency_from_id', $fromCurrency->id)
            ->where('currency_to_id', $toCurrency->id)
            ->whereDate('rate_date', $date)
            ->first();

        if ($fxRate) {
            $fxRate->update([
                'rate' => $rate,
                'source' => $reason,
            ]);
        } else {
            $fxRate = FxRate::create([
                'currency_from_id' => $fromCurrency->id,
                'currency_to_id' => $toCurrency->id,
                'rate' => $rate,
                'rate_date' => $date,
                'source' => $reason,
            ]);
        }

        // Clear cache
        $cacheKey = "fx_rate:{$fromCode}:{$toCode}:{$date->toDateString()}";
        Cache::forget($cacheKey);

        return $fxRate;
    }

    /**
     * Fetch FX rate from ECB and store it for future conversions.
     */
    public function fetchRate(string $fromCode, string $toCode, Carbon $date): ?FxRate
    {
        if ($fromCode === $toCode) {
            return null;
        }

        $fromCurrency = Currency::whereCode($fromCode)->first();
        $toCurrency = Currency::whereCode($toCode)->first();

        if (! $fromCurrency || ! $toCurrency) {
            return null;
        }

        $response = Http::timeout(30)->get('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');

        if (! $response->successful()) {
            return null;
        }

        $parsed = $this->parseEcbXml($response->body());

        if (! $parsed) {
            return null;
        }

        $rateDate = $parsed['date'];
        $rates = $parsed['rates'];

        $rateValue = $this->calculateEcbRate($fromCode, $toCode, $rates);

        if (! $rateValue) {
            return null;
        }

        FxRate::updateOrCreate([
            'currency_from_id' => $fromCurrency->id,
            'currency_to_id' => $toCurrency->id,
            'rate_date' => $rateDate,
        ], [
            'rate' => $rateValue,
            'source' => 'ecb',
        ]);

        return FxRate::where('currency_from_id', $fromCurrency->id)
            ->where('currency_to_id', $toCurrency->id)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->first();
    }

    /**
     * Override the FX rate for an individual transaction.
     */
    public function overrideRateForTransaction(int $transactionId, float $rate, string $reason): void
    {
        $transaction = Transaction::query()->findOrFail($transactionId);

        $transaction->update([
            'fx_rate' => $rate,
            'fx_source' => $reason,
            'converted_amount' => (float) $transaction->original_amount * $rate,
        ]);
    }

    /**
     * Get the most recent rate for a currency pair (used when exact date not available).
     */
    public function getLatestRate(string $fromCode, string $toCode): ?FxRate
    {
        $fromCurrency = Currency::whereCode($fromCode)->first();
        $toCurrency = Currency::whereCode($toCode)->first();

        if (! $fromCurrency || ! $toCurrency) {
            return null;
        }

        return FxRate::where('currency_from_id', $fromCurrency->id)
            ->where('currency_to_id', $toCurrency->id)
            ->orderByDesc('rate_date')
            ->first();
    }

    /**
     * Clear the FX rate cache for a specific date or all dates.
     */
    public function clearCache(?Carbon $date = null): void
    {
        if ($date) {
            $dateString = $date->toDateString();
            Cache::forget("fx_rate:*:*:{$dateString}");
        } else {
            Cache::flush();
        }
    }

    /**
     * @return array{date: Carbon, rates: array<string, float>}|null
     */
    private function parseEcbXml(string $xml): ?array
    {
        $dom = new \DOMDocument;
        if (! $dom->loadXML($xml)) {
            return null;
        }

        $elements = $dom->getElementsByTagName('Cube');
        $rateDate = null;
        $rates = [];

        foreach ($elements as $cube) {
            if ($cube->hasAttribute('time')) {
                $rateDate = Carbon::parse($cube->getAttribute('time'));
                break;
            }
        }

        if (! $rateDate) {
            return null;
        }

        foreach ($elements as $cube) {
            if ($cube->hasAttribute('currency') && $cube->hasAttribute('rate')) {
                $currency = $cube->getAttribute('currency');
                $rates[$currency] = (float) $cube->getAttribute('rate');
            }
        }

        return [
            'date' => $rateDate,
            'rates' => $rates,
        ];
    }

    /**
     * @param  array<string, float>  $rates
     */
    private function calculateEcbRate(string $fromCode, string $toCode, array $rates): ?float
    {
        if ($fromCode === 'EUR') {
            return $rates[$toCode] ?? null;
        }

        if ($toCode === 'EUR') {
            if (! isset($rates[$fromCode]) || $rates[$fromCode] === 0.0) {
                return null;
            }

            return 1.0 / $rates[$fromCode];
        }

        if (! isset($rates[$fromCode]) || ! isset($rates[$toCode]) || $rates[$fromCode] === 0.0) {
            return null;
        }

        return $rates[$toCode] / $rates[$fromCode];
    }
}
