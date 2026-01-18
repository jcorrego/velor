<?php

namespace App\Services\Finance\Parsers;

use Carbon\Carbon;

class MercuryCSVParser implements CSVParserContract
{
    /**
     * Parse Mercury (Mercury.com) CSV export format.
     * Supports the default export and the "Date (UTC)" export.
     */
    public function parse(string $filePath): array
    {
        $transactions = [];
        $rows = array_map('str_getcsv', file($filePath));

        // Skip header row and filter empty rows
        $header = $rows[0] ?? [];
        $headerMap = $this->normalizeHeader($header);

        foreach (array_slice($rows, 1) as $row) {
            if (count($row) < 4) {
                continue;
            }

            $dateRaw = $this->valueFromRow($row, $headerMap, ['date (utc)', 'date']);
            if (! $dateRaw || empty(trim($dateRaw))) {
                continue;
            }

            $description = $this->valueFromRow($row, $headerMap, ['description']) ?? '';
            $category = $this->valueFromRow($row, $headerMap, ['mercury category']);

            if (! $category || trim($category) === '') {
                $category = $this->valueFromRow($row, $headerMap, ['category']);
            }
            $amountRaw = $this->valueFromRow($row, $headerMap, ['amount']) ?? '0';
            $counterparty = $this->valueFromRow($row, $headerMap, ['name on card']);

            $date = $this->parseDate($dateRaw);

            $transactions[] = [
                'date' => $date->format('Y-m-d'),
                'description' => $this->formatDescription($description, $category),
                'amount' => (float) str_replace(',', '', trim($amountRaw)),
                'original_currency' => 'USD',
                'counterparty' => $counterparty ? trim($counterparty) : null,
                'tags' => [],
                'import_source' => 'mercury',
            ];
        }

        return $transactions;
    }

    /**
     * Get the parser name for display.
     */
    public function getName(): string
    {
        return 'Mercury';
    }

    /**
     * @return array<int, string>
     */
    private function normalizeHeader(array $header): array
    {
        return array_map(fn ($value) => strtolower(trim((string) $value)), $header);
    }

    /**
     * @param  array<int, string>  $headerMap
     * @param  array<int, string>  $keys
     */
    private function valueFromRow(array $row, array $headerMap, array $keys): ?string
    {
        foreach ($keys as $key) {
            $index = array_search($key, $headerMap, true);

            if ($index !== false && array_key_exists($index, $row)) {
                return $row[$index];
            }
        }

        return null;
    }

    private function parseDate(string $value): Carbon
    {
        $value = trim($value);

        if (str_contains($value, '/')) {
            return Carbon::createFromFormat('m/d/Y', $value);
        }

        if (str_contains($value, '-')) {
            return Carbon::createFromFormat('m-d-Y', $value);
        }

        return Carbon::parse($value);
    }

    private function formatDescription(string $description, ?string $category): string
    {
        $description = trim($description);

        if (! $category || trim($category) === '') {
            return $description;
        }

        $categoryLine = 'Category: '.trim($category);

        if ($description === '') {
            return $categoryLine;
        }

        return $description."\n".$categoryLine;
    }
}
