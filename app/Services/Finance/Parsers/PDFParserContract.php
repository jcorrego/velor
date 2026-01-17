<?php

namespace App\Services\Finance\Parsers;

interface PDFParserContract
{
    /**
     * Parse the PDF file and return an array of transaction data.
     *
     * Each transaction should have:
     * - date (Y-m-d format)
     * - description
     * - amount (decimal, positive for income, negative for expense)
     * - original_currency (e.g., 'EUR', 'COP', 'USD')
     * - counterparty (optional)
     * - tags (array)
     * - import_source (string identifier)
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $filePath): array;

    /**
     * Get a human-readable name for this parser.
     */
    public function getName(): string;
}
