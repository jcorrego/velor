<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\FxRate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEcbExchangeRates implements ShouldQueue
{
    use Queueable;

    protected const ECB_API_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    protected const TIMEOUT_SECONDS = 30;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $rates = $this->fetchEcbRates();

            if (empty($rates)) {
                Log::warning('SyncEcbExchangeRates: No rates returned from ECB API');

                return;
            }

            $this->storeRates($rates);
            Log::info('SyncEcbExchangeRates: Successfully synced '.count($rates).' exchange rates from ECB');
        } catch (\Exception $e) {
            Log::error('SyncEcbExchangeRates: Failed to sync rates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch exchange rates from ECB API.
     *
     * @return array<string, float> Array of currency code => rate pairs (rate to EUR)
     */
    protected function fetchEcbRates(): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get(self::ECB_API_URL);

            if (! $response->successful()) {
                Log::error('SyncEcbExchangeRates: ECB API returned non-200 status', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseEcbXml($response->body());
        } catch (\Exception $e) {
            Log::error('SyncEcbExchangeRates: Failed to fetch from ECB', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse ECB XML response.
     *
     * @return array<string, float> Array of currency code => rate pairs
     */
    protected function parseEcbXml(string $xml): array
    {
        try {
            $dom = new \DOMDocument;
            $dom->loadXML($xml);

            $rates = [];
            $elements = $dom->getElementsByTagName('Cube');

            $rateDate = null;
            foreach ($elements as $cube) {
                if ($cube->hasAttribute('time')) {
                    $rateDate = $cube->getAttribute('time');
                    break;
                }
            }

            if (! $rateDate) {
                Log::warning('SyncEcbExchangeRates: Could not extract rate date from ECB XML');

                return [];
            }

            // Extract individual rates
            foreach ($elements as $cube) {
                if ($cube->hasAttribute('currency') && $cube->hasAttribute('rate')) {
                    $currency = $cube->getAttribute('currency');
                    $rate = (float) $cube->getAttribute('rate');
                    $rates[$currency] = $rate;
                }
            }

            // Store the rate date for reference
            $rates['_date'] = $rateDate;

            return $rates;
        } catch (\Exception $e) {
            Log::error('SyncEcbExchangeRates: Failed to parse ECB XML', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Store rates in the database.
     *
     * @param  array<string, float>  $rates  Array of currency code => rate pairs
     */
    protected function storeRates(array $rates): void
    {
        $rateDate = isset($rates['_date'])
            ? Carbon::parse($rates['_date'])->toDateString()
            : now()->toDateString();
        unset($rates['_date']);

        // Get EUR currency (base currency)
        $eurCurrency = Currency::whereCode('EUR')->first();
        if (! $eurCurrency) {
            Log::error('SyncEcbExchangeRates: EUR currency not found in database');

            return;
        }

        // Get all active currencies to avoid storing rates for non-existent currencies
        $activeCurrencies = Currency::where('is_active', true)
            ->pluck('id', 'code')
            ->toArray();

        $stored = 0;
        foreach ($rates as $currencyCode => $rate) {
            if (! isset($activeCurrencies[$currencyCode])) {
                continue;
            }

            // Check if rate already exists for this date
            $existingRate = FxRate::where('currency_from_id', $activeCurrencies[$currencyCode])
                ->where('currency_to_id', $eurCurrency->id)
                ->whereDate('rate_date', $rateDate)
                ->first();

            if ($existingRate) {
                // Update existing rate
                $existingRate->update([
                    'rate' => $rate,
                    'source' => 'ecb',
                ]);
            } else {
                // Create new rate
                FxRate::create([
                    'currency_from_id' => $activeCurrencies[$currencyCode],
                    'currency_to_id' => $eurCurrency->id,
                    'rate' => $rate,
                    'rate_date' => $rateDate,
                    'source' => 'ecb',
                ]);
            }

            $stored++;
        }

        Log::info('SyncEcbExchangeRates: Stored '.strval($stored).' exchange rates for date '.$rateDate);
    }
}
