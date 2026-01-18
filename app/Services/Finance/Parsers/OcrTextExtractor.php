<?php

namespace App\Services\Finance\Parsers;

use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrTextExtractor
{
    public function extract(string $filePath): string
    {
        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException('File is not readable.');
        }

        try {
            if ($this->isPdf($filePath)) {
                return $this->extractFromPdf($filePath);
            }

            return trim($this->runTesseract($filePath));
        } catch (\Throwable $exception) {
            throw new \RuntimeException('OCR extraction failed: '.$exception->getMessage(), 0, $exception);
        }
    }

    protected function extractFromPdf(string $filePath): string
    {
        $tempDir = $this->createTempDir();
        $images = [];

        try {
            $images = $this->convertPdfToImages($filePath, $tempDir);
            $text = $this->extractFromImages($images);

            if ($text === '') {
                throw new \RuntimeException('OCR extraction returned no text.');
            }

            return $text;
        } finally {
            $this->cleanupTempDir($tempDir, $images);
        }
    }

    protected function isPdf(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';
    }

    /**
     * @return array<int, string>
     */
    protected function convertPdfToImages(string $filePath, string $tempDir): array
    {
        $pdftoppm = config('ocr.pdftoppm_path', 'pdftoppm');
        $dpi = (int) config('ocr.dpi', 200);
        $dpi = $dpi > 0 ? $dpi : 200;
        $outputPrefix = $tempDir.DIRECTORY_SEPARATOR.'page';
        $command = sprintf(
            '%s -png -r %d %s %s 2>&1',
            escapeshellcmd((string) $pdftoppm),
            $dpi,
            escapeshellarg($filePath),
            escapeshellarg($outputPrefix)
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('PDF conversion failed: '.implode("\n", $output));
        }

        $images = glob($outputPrefix.'-*.png') ?: [];
        sort($images);

        if ($images === []) {
            throw new \RuntimeException('PDF conversion produced no images.');
        }

        return $images;
    }

    /**
     * @param  array<int, string>  $images
     */
    protected function extractFromImages(array $images): string
    {
        $chunks = [];

        foreach ($images as $image) {
            $chunks[] = trim($this->runTesseract($image));
        }

        return trim(implode("\n\n", array_filter($chunks)));
    }

    protected function runTesseract(string $filePath): string
    {
        $tesseract = new TesseractOCR($filePath);
        $language = config('ocr.language');

        if (is_string($language) && $language !== '') {
            $tesseract->lang($language);
        }

        $executable = config('ocr.tesseract_path');

        if (is_string($executable) && $executable !== '') {
            $tesseract->executable($executable);
        }

        return (string) $tesseract->run();
    }

    protected function createTempDir(): string
    {
        $base = config('ocr.temp_path');

        if (! is_string($base) || $base === '') {
            $base = sys_get_temp_dir();
        }

        $dir = rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'ocr_'.Str::uuid();

        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Failed to create OCR temp directory.');
        }

        return $dir;
    }

    /**
     * @param  array<int, string>  $images
     */
    protected function cleanupTempDir(string $tempDir, array $images): void
    {
        foreach ($images as $image) {
            if (is_file($image)) {
                unlink($image);
            }
        }

        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }
}
