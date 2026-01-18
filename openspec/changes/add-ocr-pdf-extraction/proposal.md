# Change: Add OCR Fallback for PDF Statement Parsing

## Why
Some bank statements render transaction tables as non-text content, so the current text extraction fails to find transactions. OCR fallback ensures PDF imports work even when text extraction returns only headers.

## What Changes
- Add a hosted OCR fallback path for PDF statement parsing when no transactions are detected via text extraction.
- Surface OCR-related parsing errors clearly during preview/import.
- Keep OCR usage limited to supported parsers and only when text extraction yields no rows.

## Impact
- Affected specs: finance-management
- Affected code: PDF text extraction service, PDF parsers, import preview flow
- Dependencies: add AWS Textract API client and configuration
