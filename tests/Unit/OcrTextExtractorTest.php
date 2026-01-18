<?php

use App\Services\Finance\Parsers\OcrTextExtractor;

it('uses PDF extraction flow for pdf files', function () {
    $file = tempnam(sys_get_temp_dir(), 'ocr_pdf_');
    $pdfFile = $file.'.pdf';
    rename($file, $pdfFile);
    file_put_contents($pdfFile, 'placeholder');

    $extractor = new class extends OcrTextExtractor
    {
        public bool $pdfCalled = false;

        protected function extractFromPdf(string $filePath): string
        {
            $this->pdfCalled = true;

            return 'PDF OCR text';
        }

        protected function runTesseract(string $filePath): string
        {
            throw new RuntimeException('Tesseract should not be used directly for PDF.');
        }
    };

    try {
        expect($extractor->extract($pdfFile))->toBe('PDF OCR text');
        expect($extractor->pdfCalled)->toBeTrue();
    } finally {
        unlink($pdfFile);
    }
});

it('uses tesseract directly for non-pdf files', function () {
    $file = tempnam(sys_get_temp_dir(), 'ocr_img_');
    file_put_contents($file, 'placeholder');

    $extractor = new class extends OcrTextExtractor
    {
        public bool $tesseractCalled = false;

        protected function extractFromPdf(string $filePath): string
        {
            throw new RuntimeException('PDF flow should not be used.');
        }

        protected function runTesseract(string $filePath): string
        {
            $this->tesseractCalled = true;

            return 'Image OCR text';
        }
    };

    try {
        expect($extractor->extract($file))->toBe('Image OCR text');
        expect($extractor->tesseractCalled)->toBeTrue();
    } finally {
        unlink($file);
    }
});

it('throws when the OCR file is missing', function () {
    $extractor = new OcrTextExtractor;

    expect(fn () => $extractor->extract('/tmp/missing-ocr.pdf'))
        ->toThrow(InvalidArgumentException::class);
});
