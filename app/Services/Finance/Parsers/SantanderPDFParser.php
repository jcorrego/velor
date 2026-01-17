<?php

namespace App\Services\Finance\Parsers;

use Carbon\Carbon;

class SantanderPDFParser implements PDFParserContract
{
    /**
     * Parse Banco Santander PDF statement format.
     *
     * Expected text rows (simple baseline):
     * YYYY-MM-DD Description Amount
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $filePath): array
    {
        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException('PDF file is not readable.');
        }

        $contents = file_get_contents($filePath);

        if ($contents === false || trim($contents) === '') {
            throw new \RuntimeException('PDF appears to be empty.');
        }

        $pattern = '/(?P<date>\\d{4}-\\d{2}-\\d{2})\\s+(?P<description>.+?)\\s+(?P<amount>-?\\d+(?:\\.\\d{2})?)/m';
        preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            throw new \RuntimeException('No transactions found in PDF.');
        }

        $transactions = [];

        foreach ($matches as $match) {
            $transactions[] = [
                'date' => Carbon::parse($match['date'])->format('Y-m-d'),
                'description' => trim($match['description']),
                'amount' => (float) $match['amount'],
                'original_currency' => 'EUR',
                'counterparty' => null,
                'tags' => [],
                'import_source' => 'banco_santander_pdf',
            ];
        }

        return $transactions;
    }

    /**
     * Get the parser name for display.
     */
    public function getName(): string
    {
        return 'Banco Santander (PDF)';
    }
}
