<?php

namespace App\Services\Finance\Parsers;

use Carbon\Carbon;

class SantanderCSVParser implements CSVParserContract
{
    /**
     * Parse Banco Santander CSV export format.
     * Expected columns: Fecha,Movimiento,Cantidad,Saldo,Referencia
     */
    public function parse(string $filePath): array
    {
        $transactions = [];
        $rows = array_map(fn ($line) => str_getcsv($line, ';'), file($filePath));

        // Skip header row and filter empty rows
        foreach (array_slice($rows, 1) as $row) {
            if (count($row) < 5 || empty(trim($row[0]))) {
                continue;
            }

            $transactions[] = [
                'date' => Carbon::createFromFormat('d/m/Y', trim($row[0]))->format('Y-m-d'),
                'description' => trim($row[1]),
                'amount' => (float) str_replace(',', '.', trim($row[2])),
                'original_currency' => 'EUR', // Santander España uses EUR
                'counterparty' => null,
                'tags' => [],
                'import_source' => 'banco_santander',
            ];
        }

        return $transactions;
    }

    /**
     * Get the parser name for display.
     */
    public function getName(): string
    {
        return 'Banco Santander';
    }
}
