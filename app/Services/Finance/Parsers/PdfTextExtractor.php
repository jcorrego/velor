<?php

namespace App\Services\Finance\Parsers;

use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    public function extract(string $filePath): string
    {
        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException('PDF file is not readable.');
        }

        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);

            return (string) $pdf->getText();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('PDF text extraction failed: '.$exception->getMessage(), 0, $exception);
        }
    }
}
