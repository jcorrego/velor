<?php

namespace App\Finance\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\TransactionImport;
use Carbon\Carbon;
use League\Csv\Reader;

class TransactionImportService
{
    public function __construct(private FxRateService $fxRateService) {}

    /**
     * Import transactions from a CSV or PDF file.
     * Returns import result with statistics.
     */
    public function importFromFile(Account $account, string $filePath, string $fileName): array
    {
        $mimeType = mime_content_type($filePath);
        $parsed = [];

        if ($this->isCSVFile($filePath)) {
            $parsed = $this->parseCSV($filePath, $account);
        } elseif ($this->isPDFFile($filePath)) {
            $parsed = $this->parsePDF($filePath, $account);
        } else {
            return [
                'success' => false,
                'message' => 'Unsupported file type. Please use CSV or PDF.',
                'parsed_count' => 0,
                'matched_count' => 0,
            ];
        }

        $matched = $this->matchAndCreateTransactions($account, $parsed);

        // Record import
        TransactionImport::create([
            'account_id' => $account->id,
            'file_type' => pathinfo($filePath, PATHINFO_EXTENSION),
            'file_name' => $fileName,
            'parsed_count' => count($parsed),
            'matched_count' => count($matched),
            'imported_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => sprintf('%d of %d transactions imported successfully', count($matched), count($parsed)),
            'parsed_count' => count($parsed),
            'matched_count' => count($matched),
            'transactions' => $matched,
        ];
    }

    /**
     * Parse CSV file into transaction data array.
     * Supports Banco Santander and Mercury CSV formats.
     */
    private function parseCSV(string $filePath, Account $account): array
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $transactions = [];

        foreach ($csv->getRecords() as $record) {
            // Support both Banco Santander and Mercury formats
            $transaction = $this->parseCSVRecord($record, $account);

            if ($transaction) {
                $transactions[] = $transaction;
            }
        }

        return $transactions;
    }

    /**
     * Parse a single CSV record.
     */
    private function parseCSVRecord(array $record, Account $account): ?array
    {
        // Map common column names from different banks
        $dateField = $this->findField($record, ['Date', 'Fecha', 'Transaction Date', 'date']);
        $amountField = $this->findField($record, ['Amount', 'Cantidad', 'Importe', 'amount']);
        $descriptionField = $this->findField($record, ['Description', 'Descripción', 'Concept', 'Concepto', 'description']);
        $counterpartyField = $this->findField($record, ['Counterparty', 'Contrapartida', 'Name', 'Nombre', 'counterparty']);

        if (! $dateField || ! $amountField) {
            return null;
        }

        $amount = floatval(str_replace([',', ' '], ['.', ''], $record[$amountField]));
        $date = $this->parseDate($record[$dateField]);

        if (! $date) {
            return null;
        }

        return [
            'transaction_date' => $date,
            'original_amount' => abs($amount),
            'original_currency_id' => $account->currency_id,
            'type' => $amount < 0 ? 'expense' : 'income',
            'counterparty_name' => $record[$counterpartyField] ?? null,
            'description' => $record[$descriptionField] ?? null,
            'import_source' => 'csv',
        ];
    }

    /**
     * Parse PDF file into transaction data array.
     * Placeholder implementation - would require PDF parsing library.
     */
    private function parsePDF(string $filePath, Account $account): array
    {
        // Placeholder - in production, use a PDF parsing library
        return [];
    }

    /**
     * Find a field by checking multiple possible column names.
     */
    private function findField(array $record, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            if (isset($record[$name]) && ! empty($record[$name])) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Parse date string in various formats.
     */
    private function parseDate(string $dateString): ?Carbon
    {
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($dateString));
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Match parsed transactions and create them, handling duplicates.
     */
    private function matchAndCreateTransactions(Account $account, array $parsed): array
    {
        $created = [];

        foreach ($parsed as $transactionData) {
            // Check for duplicate (same date, amount, account)
            $existing = Transaction::query()
                ->where('account_id', $account->id)
                ->where('transaction_date', $transactionData['transaction_date'])
                ->where('original_amount', $transactionData['original_amount'])
                ->where('description', $transactionData['description'])
                ->first();

            if ($existing) {
                continue; // Skip duplicate
            }

            // Get converted amount using FX service
            $convertedAmount = $this->fxRateService->convert(
                $transactionData['original_amount'],
                $account->currency,
                Currency::where('code', 'EUR')->first(), // User's base currency
                $transactionData['transaction_date']
            );

            $rate = $convertedAmount / ($transactionData['original_amount'] ?: 1);

            // Create transaction
            $transaction = Transaction::create([
                ...$transactionData,
                'account_id' => $account->id,
                'converted_amount' => $convertedAmount,
                'converted_currency_id' => Currency::where('code', 'EUR')->first()->id,
                'fx_rate' => $rate,
                'fx_source' => 'ecb',
            ]);

            $created[] = $transaction;
        }

        return $created;
    }

    /**
     * Check if file is CSV.
     */
    private function isCSVFile(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'csv';
    }

    /**
     * Check if file is PDF.
     */
    private function isPDFFile(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';
    }
}
