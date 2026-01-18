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
}
