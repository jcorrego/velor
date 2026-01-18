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
        $contents = app(PdfTextExtractor::class)->extract($filePath);

        if (trim($contents) === '') {
            throw new \RuntimeException('PDF appears to be empty.');
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $contents))));
        $transactions = [];
        $currentDate = null;
        $descriptionLines = [];

        foreach ($lines as $line) {
            if (preg_match('/^\\d{2}\\/\\d{2}\\/\\d{4}$/', $line) === 1) {
                $currentDate = $line;
                $descriptionLines = [];

                continue;
            }

            if (str_starts_with(strtolower($line), 'fecha valor')) {
                continue;
            }

            if ($currentDate && preg_match('/(?P<amount>-?\\d{1,3}(?:\\.\\d{3})*,\\d{2})\\s*EUR/i', $line, $amountMatch) === 1) {
                $rawAmount = $amountMatch['amount'];
                $normalizedAmount = str_replace(['.', ','], ['', '.'], $rawAmount);

                $transactions[] = [
                    'date' => Carbon::createFromFormat('d/m/Y', $currentDate)->format('Y-m-d'),
                    'description' => trim(implode(' ', $descriptionLines)) ?: 'Transaction',
                    'amount' => (float) $normalizedAmount,
                    'original_currency' => 'EUR',
                    'counterparty' => null,
                    'tags' => [],
                    'import_source' => 'banco_santander_pdf',
                ];

                $currentDate = null;
                $descriptionLines = [];

                continue;
            }

            if ($currentDate) {
                $descriptionLines[] = $line;
            }
        }

        if ($transactions !== []) {
            return $transactions;
        }

        $pattern = '/(?P<date>\\d{4}-\\d{2}-\\d{2})\\s+(?P<description>.+?)\\s+(?P<amount>-?\\d+(?:\\.\\d{2})?)/m';
        preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            throw new \RuntimeException('No transactions found in PDF.');
        }

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
