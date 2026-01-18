<?php

namespace App\Services\Finance\Parsers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MercuryPDFParser implements PDFParserContract
{
    /**
     * Parse Mercury PDF statement format.
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

        $transactions = $this->parseContents($contents);

        if ($transactions === []) {
            $ocrContents = app(OcrTextExtractor::class)->extract($filePath);
            $transactions = $this->parseContents($ocrContents);

            if ($transactions === [] && config('ocr.log_output')) {
                $activitySnippet = $this->extractSectionSnippet($ocrContents, 'Account activity');

                Log::debug('Mercury PDF OCR produced no parseable transactions.', [
                    'length' => mb_strlen($ocrContents),
                    'snippet' => mb_substr($ocrContents, 0, 500),
                    'account_activity' => $activitySnippet,
                ]);
            }
        }

        if ($transactions === []) {
            throw new \RuntimeException('No transactions found in PDF.');
        }

        return $transactions;
    }

    /**
     * Get the parser name for display.
     */
    public function getName(): string
    {
        return 'Mercury (PDF)';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseContents(string $contents): array
    {
        $pattern = '/(?P<date>\\d{4}-\\d{2}-\\d{2})\\s+(?P<description>.+?)\\s+(?P<amount>-?\\d+(?:\\.\\d{2})?)/m';
        preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return $this->parseOcrContents($contents);
        }

        $transactions = [];

        foreach ($matches as $match) {
            $transactions[] = [
                'date' => Carbon::parse($match['date'])->format('Y-m-d'),
                'description' => trim($match['description']),
                'amount' => (float) $match['amount'],
                'original_currency' => 'USD',
                'counterparty' => null,
                'tags' => [],
                'import_source' => 'mercury_pdf',
            ];
        }

        return $transactions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseOcrContents(string $contents): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\\R/', $contents))));
        $startIndex = $this->findLineIndex($lines, 'Date (UTC)');

        if ($startIndex === null) {
            $startIndex = $this->findLineIndex($lines, 'Date');
        }

        if ($startIndex === null) {
            return [];
        }

        $descriptionIndex = $this->findLineIndex($lines, 'Description', $startIndex + 1);
        $typeIndex = $this->findLineIndex($lines, 'Type', $descriptionIndex !== null ? $descriptionIndex + 1 : $startIndex + 1);

        if ($descriptionIndex === null || $typeIndex === null) {
            return [];
        }

        $dates = array_slice($lines, $startIndex + 1, $descriptionIndex - $startIndex - 1);
        $descriptions = array_slice($lines, $descriptionIndex + 1, $typeIndex - $descriptionIndex - 1);
        $typeLines = $this->sliceTypeLines($lines, $typeIndex + 1);

        $amounts = $this->extractAmounts($typeLines);
        $year = $this->extractStatementYear($contents);
        $count = min(count($dates), count($descriptions), count($amounts));

        if ($count === 0) {
            return [];
        }

        $transactions = [];

        for ($i = 0; $i < $count; $i++) {
            $date = $this->normalizeOcrDate($dates[$i], $year);

            if ($date === null) {
                continue;
            }

            $transactions[] = [
                'date' => $date,
                'description' => trim($descriptions[$i]),
                'amount' => $amounts[$i],
                'original_currency' => 'USD',
                'counterparty' => null,
                'tags' => [],
                'import_source' => 'mercury_pdf',
            ];
        }

        return $transactions;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function findLineIndex(array $lines, string $needle, int $startAt = 0): ?int
    {
        $count = count($lines);

        for ($i = $startAt; $i < $count; $i++) {
            if (strcasecmp($lines[$i], $needle) === 0) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function sliceTypeLines(array $lines, int $startIndex): array
    {
        $stopMarkers = [
            'Daily balances',
            'Statement balance',
            'Beginning Balance',
            'Ending Balance',
            'Account details',
            'Account activity',
        ];

        $slice = [];
        $count = count($lines);

        for ($i = $startIndex; $i < $count; $i++) {
            foreach ($stopMarkers as $marker) {
                if (str_starts_with($lines[$i], $marker)) {
                    return $slice;
                }
            }

            $slice[] = $lines[$i];
        }

        return $slice;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, float>
     */
    private function extractAmounts(array $lines): array
    {
        $amounts = [];

        foreach ($lines as $line) {
            $token = $this->extractNumericToken($line);

            if ($token === null) {
                continue;
            }

            $amounts[] = $this->normalizeAmountToken($token);
        }

        return $amounts;
    }

    private function extractNumericToken(string $line): ?string
    {
        $normalized = str_replace(['$', '€', '£', '¥'], '', $line);

        if (preg_match('/[-+]?\\d[\\d,\\.]*$/', trim($normalized), $match) !== 1) {
            return null;
        }

        return $match[0];
    }

    private function normalizeAmountToken(string $token): float
    {
        $token = trim($token);
        $isNegative = str_contains($token, '-') || str_starts_with($token, '(');
        $token = trim($token, '()');
        $token = str_replace(['+', '-'], '', $token);

        if (str_contains($token, ',') && str_contains($token, '.')) {
            $token = str_replace(',', '', $token);
        } elseif (str_contains($token, ',')) {
            if (preg_match('/,\\d{2}$/', $token) === 1) {
                $token = str_replace(',', '.', $token);
            } else {
                $token = str_replace(',', '', $token);
            }
        }

        if (! str_contains($token, '.') && strlen($token) > 2) {
            $token = substr($token, 0, -2).'.'.substr($token, -2);
        }

        $value = (float) $token;

        return $isNegative ? -$value : $value;
    }

    private function extractStatementYear(string $contents): int
    {
        if (preg_match('/\\b(20\\d{2})\\b/', $contents, $match) === 1) {
            return (int) $match[1];
        }

        return (int) date('Y');
    }

    private function normalizeOcrDate(string $value, int $year): ?string
    {
        $value = trim($value);

        try {
            $date = Carbon::parse("{$value} {$year}");

            return $date->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractSectionSnippet(string $contents, string $needle): string
    {
        $offset = mb_stripos($contents, $needle);

        if ($offset === false) {
            return '';
        }

        return mb_substr($contents, $offset, 1500);
    }
}
