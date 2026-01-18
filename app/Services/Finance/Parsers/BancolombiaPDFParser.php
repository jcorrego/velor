<?php

namespace App\Services\Finance\Parsers;

use Carbon\Carbon;

class BancolombiaPDFParser implements PDFParserContract
{
    /**
     * Parse Bancolombia PDF statement format.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $filePath): array
    {
        $contents = app(PdfTextExtractor::class)->extract($filePath);

        if (trim($contents) === '') {
            throw new \RuntimeException('PDF appears to be empty.');
        }

        $period = $this->extractStatementPeriod($contents);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\\R/', $contents)), fn ($line) => $line !== ''));

        $transactions = [];
        $sections = $this->extractTransactionSections($lines);

        foreach ($sections as $sectionLines) {
            $transactions = array_merge(
                $transactions,
                $this->parseSection($sectionLines, $period['start'] ?? null, $period['end'] ?? null)
            );
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
        return 'Bancolombia (PDF)';
    }

    /**
     * @return array{start: Carbon|null, end: Carbon|null}
     */
    private function extractStatementPeriod(string $contents): array
    {
        if (preg_match('/DESDE:(\\d{4})\\/(\\d{2})\\/(\\d{2})\\s+HASTA:(\\d{4})\\/(\\d{2})\\/(\\d{2})/u', $contents, $matches) === 1) {
            return [
                'start' => Carbon::createFromFormat('Y/m/d', "{$matches[1]}/{$matches[2]}/{$matches[3]}"),
                'end' => Carbon::createFromFormat('Y/m/d', "{$matches[4]}/{$matches[5]}/{$matches[6]}"),
            ];
        }

        return ['start' => null, 'end' => null];
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array<int, string>>
     */
    private function extractTransactionSections(array $lines): array
    {
        $sections = [];
        $sectionStart = null;
        $headerPattern = '/^FECHA\\s+DESCRIPCI/i';

        foreach ($lines as $index => $line) {
            if (preg_match($headerPattern, $line) === 1) {
                if ($sectionStart !== null) {
                    $sections[] = array_slice($lines, $sectionStart, $index - $sectionStart);
                }

                $sectionStart = $index + 1;
            }
        }

        if ($sectionStart !== null) {
            $sections[] = array_slice($lines, $sectionStart);
        }

        return $sections;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseSection(array $lines, ?Carbon $periodStart, ?Carbon $periodEnd): array
    {
        $inlineTransactions = $this->parseInlineRows($lines, $periodStart, $periodEnd);

        if ($inlineTransactions !== []) {
            return $inlineTransactions;
        }

        return $this->parseColumnarRows($lines, $periodStart, $periodEnd);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseInlineRows(array $lines, ?Carbon $periodStart, ?Carbon $periodEnd): array
    {
        $transactions = [];
        $pattern = '/^(?P<date>\\d{1,2}\\/\\d{1,2})\\s+(?P<description>.+?)\\s+(?P<amount>-?[\\d,.]+)\\s+(?P<balance>-?[\\d,.]+)$/u';

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $match) !== 1) {
                continue;
            }

            $transactions[] = [
                'date' => $this->normalizeDate($match['date'], $periodStart, $periodEnd),
                'description' => trim($match['description']),
                'amount' => $this->parseAmount($match['amount']),
                'original_currency' => 'COP',
                'counterparty' => null,
                'tags' => [],
                'import_source' => 'bancolombia_pdf',
            ];
        }

        return $transactions;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseColumnarRows(array $lines, ?Carbon $periodStart, ?Carbon $periodEnd): array
    {
        $datePattern = '/^\\d{1,2}\\/\\d{1,2}$/';
        $count = count($lines);
        $index = 0;
        $dates = [];

        while ($index < $count && preg_match($datePattern, $lines[$index]) === 1) {
            $dates[] = $lines[$index];
            $index++;
        }

        if ($dates === []) {
            return [];
        }

        $descriptions = [];

        while ($index < $count && ! $this->isAmountLine($lines[$index]) && ! $this->isSectionEnd($lines[$index])) {
            $descriptions[] = $lines[$index];
            $index++;
        }

        while ($index < $count && $this->isSectionEnd($lines[$index])) {
            $index++;
        }

        while ($index < $count && ! $this->isAmountLine($lines[$index])) {
            $index++;
        }

        $amounts = $this->collectNumericLines($lines, $index, count($dates));
        $balances = $this->collectNumericLines($lines, $index, count($dates));

        $max = min(count($dates), count($descriptions), count($amounts));
        $transactions = [];

        for ($i = 0; $i < $max; $i++) {
            $transactions[] = [
                'date' => $this->normalizeDate($dates[$i], $periodStart, $periodEnd),
                'description' => trim($descriptions[$i]),
                'amount' => $this->parseAmount($amounts[$i]),
                'original_currency' => 'COP',
                'counterparty' => null,
                'tags' => [],
                'import_source' => 'bancolombia_pdf',
            ];
        }

        return $transactions;
    }

    private function normalizeDate(string $date, ?Carbon $periodStart, ?Carbon $periodEnd): string
    {
        [$day, $month] = array_map('intval', explode('/', $date));
        $year = $this->resolveYearForMonth($month, $periodStart, $periodEnd);

        return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
    }

    private function resolveYearForMonth(int $month, ?Carbon $periodStart, ?Carbon $periodEnd): int
    {
        if ($periodStart === null || $periodEnd === null) {
            return (int) date('Y');
        }

        if ($periodStart->year === $periodEnd->year) {
            return $periodEnd->year;
        }

        if ($month >= $periodStart->month) {
            return $periodStart->year;
        }

        return $periodEnd->year;
    }

    private function isAmountLine(string $value): bool
    {
        return preg_match('/^-?\\d{1,3}(?:,\\d{3})*(?:\\.\\d{2})?$/', $value) === 1;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function collectNumericLines(array $lines, int &$index, int $limit): array
    {
        $values = [];
        $count = count($lines);

        while ($index < $count && $this->isAmountLine($lines[$index])) {
            $values[] = $lines[$index];
            $index++;

            if (count($values) >= $limit) {
                break;
            }
        }

        return $values;
    }

    private function isSectionEnd(string $line): bool
    {
        return str_starts_with(mb_strtoupper($line), 'FIN ESTADO');
    }

    private function parseAmount(string $value): float
    {
        return (float) str_replace(',', '', $value);
    }
}
