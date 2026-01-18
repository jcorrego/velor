<?php

namespace App\Services\Documents;

use App\Services\Finance\Parsers\OcrTextExtractor;
use App\Services\Finance\Parsers\PdfTextExtractor;

class DocumentTextExtractor
{
    public function __construct(
        public PdfTextExtractor $pdfTextExtractor,
        public OcrTextExtractor $ocrTextExtractor,
    ) {}

    public function extractText(string $filePath): string
    {
        if ($this->isPdf($filePath)) {
            return $this->extractPdfText($filePath);
        }

        return $this->ocrTextExtractor->extract($filePath);
    }

    public function extractPdfText(string $filePath): string
    {
        $text = $this->pdfTextExtractor->extract($filePath);

        if (trim($text) === '') {
            $text = $this->ocrTextExtractor->extract($filePath);
        }

        if (trim($text) === '') {
            throw new \RuntimeException('Document text extraction failed.');
        }

        return $text;
    }

    private function isPdf(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';
    }
}
