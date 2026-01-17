<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionImport;
use App\Services\Finance\Parsers\BancolombiaCSVParser;
use App\Services\Finance\Parsers\CSVParserContract;
use App\Services\Finance\Parsers\MercuryCSVParser;
use App\Services\Finance\Parsers\SantanderCSVParser;
use Carbon\Carbon;

class TransactionImportService
{
    /**
     * Parse a CSV file using the appropriate parser.
     */
    public function parseCSV(string $filePath, string $parserType): array
    {
        $parser = $this->resolveParser($parserType);

        return $parser->parse($filePath);
    }

    /**
     * Parse a PDF statement into an array of transactions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parsePDF(string $filePath, int $accountId): array
    {
        Account::query()->findOrFail($accountId);

        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException('PDF file is not readable.');
        }

        return [];
    }

    /**
     * Match imported transactions against existing ones to detect duplicates.
     * Returns array with 'matched' and 'unmatched' keys.
     */
    public function matchTransactions(array $importedTransactions, Account $account): array
    {
        $existingTransactions = $account->transactions()
            ->select('transaction_date as date', 'original_amount as amount', 'description')
            ->get()
            ->keyBy(fn ($t) => $this->transactionSignature($t));

        $matched = [];
        $unmatched = [];

        foreach ($importedTransactions as $imported) {
            $signature = $this->createSignature($imported);

            if ($existingTransactions->has($signature)) {
                $matched[] = array_merge($imported, ['duplicate' => true]);
            } else {
                $unmatched[] = array_merge($imported, ['duplicate' => false]);
            }
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
            'total' => count($importedTransactions),
            'duplicates' => count($matched),
            'new' => count($unmatched),
        ];
    }

    /**
     * Import transactions into the database.
     * Returns count of imported transactions.
     */
    public function importTransactions(array $transactions, Account $account, string $source): int
    {
        $imported = 0;

        foreach ($transactions as $data) {
            // Skip if it's marked as duplicate
            if (isset($data['duplicate']) && $data['duplicate']) {
                continue;
            }

            $categoryId = app(TransactionCategorizationService::class)
                ->resolveCategoryId($data, $account);

            Transaction::create([
                'account_id' => $account->id,
                'transaction_date' => $data['date'],
                'description' => $data['description'],
                'original_amount' => $data['amount'],
                'original_currency_id' => $this->getCurrencyId($data['original_currency'] ?? '', $account->currency_id),
                'converted_amount' => $data['amount'], // TODO: implement FX conversion
                'converted_currency_id' => $account->currency_id,
                'fx_rate' => 1.0, // TODO: fetch from FxRateService
                'fx_source' => 'import',
                'type' => $data['amount'] >= 0 ? 'income' : 'expense',
                'counterparty_name' => $data['counterparty'] ?? null,
                'category_id' => $categoryId,
                'import_source' => $source,
                'tags' => $data['tags'] ?? [],
            ]);

            $imported++;
        }

        // Log the import
        TransactionImport::create([
            'account_id' => $account->id,
            'file_name' => 'import-'.now()->timestamp.'.csv',
            'file_type' => 'csv',
            'parsed_count' => count($transactions),
            'imported_count' => $imported,
            'imported_at' => now(),
        ]);

        return $imported;
    }

    /**
     * Get available parsers.
     */
    public function getAvailableParsers(): array
    {
        return [
            'santander' => SantanderCSVParser::class,
            'mercury' => MercuryCSVParser::class,
            'bancolombia' => BancolombiaCSVParser::class,
        ];
    }

    /**
     * Resolve parser class by type.
     */
    private function resolveParser(string $type): CSVParserContract
    {
        $parsers = $this->getAvailableParsers();

        if (! isset($parsers[$type])) {
            throw new \InvalidArgumentException("Unknown parser type: {$type}");
        }

        return app($parsers[$type]);
    }

    /**
     * Create a unique signature for transaction matching.
     * Signature: date|amount|first 30 chars of description
     */
    private function createSignature(array $transaction): string
    {
        return $this->transactionSignature([
            'date' => $transaction['date'],
            'amount' => $transaction['amount'],
            'description' => $transaction['description'] ?? '',
        ]);
    }

    /**
     * Generate signature from transaction object or array.
     */
    private function transactionSignature($transaction): string
    {
        $date = $transaction->date ?? $transaction['date'];
        $normalizedDate = $date instanceof \Carbon\CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date)->toDateString();
        $amount = $transaction->amount ?? $transaction['amount'];
        $description = $transaction->description ?? $transaction['description'] ?? '';

        // Normalize amount to 2 decimal places for consistent matching
        $normalizedAmount = number_format((float) $amount, 2, '.', '');

        return "{$normalizedDate}|{$normalizedAmount}|".substr(md5($description), 0, 8);
    }

    /**
     * Get currency ID by code.
     */
    private function getCurrencyId(string $code, ?int $fallbackCurrencyId = null): int
    {
        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            if ($fallbackCurrencyId) {
                return $fallbackCurrencyId;
            }

            throw new \InvalidArgumentException('Currency code is required.');
        }

        $existing = \App\Models\Currency::where('code', $normalized)->first();

        if ($existing) {
            return $existing->id;
        }

        $enum = \App\Enums\Finance\Currency::tryFrom($normalized);

        $currency = \App\Models\Currency::create([
            'code' => $normalized,
            'name' => $enum?->label() ?? $normalized,
            'symbol' => $enum?->symbol() ?? $normalized,
            'is_active' => true,
        ]);

        return $currency->id;
    }
}
