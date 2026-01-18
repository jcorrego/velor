# Change: Add OCR Fallback for PDF Statement Parsing

## Why
Some bank statements render transaction tables as non-text content, so the current text extraction fails to find transactions. OCR fallback ensures PDF imports work even when text extraction returns only headers, and the same OCR capability is useful for legal document ingestion.

## What Changes
- Add a Tesseract OCR fallback path for PDF statement parsing when no transactions are detected via text extraction.
- Expose OCR extraction for legal document ingestion so document text is searchable.
- Surface OCR-related parsing errors clearly during preview/import.
- Keep OCR usage limited to supported parsers and only when text extraction yields no rows.

## Impact
- Affected specs: finance-management
- Affected code: PDF text extraction service, PDF parsers, import preview flow
- Dependencies: add Tesseract runtime dependency, configuration, and the tesseract-ocr-for-php wrapper
