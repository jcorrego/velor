<?php

namespace App\Services\Finance\Parsers;

use Carbon\Carbon;

class MercuryCSVParser implements CSVParserContract
{
    /**
     * Parse Mercury (Mercury.com) CSV export format.
     * Expected columns: Date,Description,Amount,Balance,Type
     * Currency: USD
     */
    public function parse(string $filePath): array
    {
        $transactions = [];
        $rows = array_map('str_getcsv', file($filePath));

        // Skip header row and filter empty rows
        foreach (array_slice($rows, 1) as $row) {
            if (count($row) < 4 || empty(trim($row[0]))) {
                continue;
            }

            $transactions[] = [
                'date' => Carbon::createFromFormat('m/d/Y', trim($row[0]))->format('Y-m-d'),
                'description' => trim($row[1]),
                'amount' => (float) trim($row[2]),
                'original_currency' => 'USD',
                'counterparty' => null,
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
}
