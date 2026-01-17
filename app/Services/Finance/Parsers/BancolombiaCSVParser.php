<?php

namespace App\Services\Finance\Parsers;

use Carbon\Carbon;

class BancolombiaCSVParser implements CSVParserContract
{
    /**
     * Parse Bancolombia CSV export format.
     * Expected columns: Fecha,Descripción,Débito,Crédito,Saldo
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

            // Bancolombia separates debits and credits
            $debit = (float) str_replace(',', '.', trim($row[2]));
            $credit = (float) str_replace(',', '.', trim($row[3]));
            $amount = $credit > 0 ? $credit : -$debit;

            $transactions[] = [
                'date' => Carbon::createFromFormat('d/m/Y', trim($row[0]))->format('Y-m-d'),
                'description' => trim($row[1]),
                'amount' => $amount,
                'original_currency' => 'COP', // Bancolombia Colombia uses COP
                'counterparty' => null,
                'tags' => [],
                'import_source' => 'bancolombia',
            ];
        }

        return $transactions;
    }

    /**
     * Get the parser name for display.
     */
    public function getName(): string
    {
        return 'Bancolombia';
    }
}
