<?php

use App\Services\Documents\DocumentTextExtractor;
use App\Services\Finance\Parsers\OcrTextExtractor;
use App\Services\Finance\Parsers\PdfTextExtractor;

it('returns embedded pdf text when available', function () {
    $pdfExtractor = new class extends PdfTextExtractor
    {
        public function extract(string $filePath): string
        {
            return 'Embedded PDF text.';
        }
    };

    $ocrExtractor = new class extends OcrTextExtractor
    {
        public function extract(string $filePath): string
        {
            throw new RuntimeException('OCR should not be called.');
        }
    };

    $extractor = new DocumentTextExtractor($pdfExtractor, $ocrExtractor);

    expect($extractor->extractPdfText(__FILE__))->toBe('Embedded PDF text.');
});

it('falls back to OCR when PDF text is empty', function () {
    $pdfExtractor = new class extends PdfTextExtractor
    {
        public function extract(string $filePath): string
        {
            return '';
        }
    };

    $ocrExtractor = new class extends OcrTextExtractor
    {
        public function extract(string $filePath): string
        {
            return 'OCR text.';
        }
    };

    $extractor = new DocumentTextExtractor($pdfExtractor, $ocrExtractor);

    expect($extractor->extractPdfText(__FILE__))->toBe('OCR text.');
});

it('throws when no text can be extracted', function () {
    $pdfExtractor = new class extends PdfTextExtractor
    {
        public function extract(string $filePath): string
        {
            return '';
        }
    };

    $ocrExtractor = new class extends OcrTextExtractor
    {
        public function extract(string $filePath): string
        {
            return '';
        }
    };

    $extractor = new DocumentTextExtractor($pdfExtractor, $ocrExtractor);

    expect(fn () => $extractor->extractPdfText(__FILE__))
        ->toThrow(RuntimeException::class);
});
