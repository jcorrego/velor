## Context
Some PDF statements (notably Mercury) only expose transaction tables as non-text content. Text extraction yields only headers, so parsing fails. We need an OCR fallback that converts PDF pages to text before parsing.
Current UI limits PDF imports to Santander and Bancolombia while Mercury remains CSV-only until OCR fallback is available.

## Goals / Non-Goals
- Goals:
  - Extract text from PDF pages when text extraction yields zero transactions.
  - Keep OCR usage explicit, minimal, and limited to supported parsers.
  - Provide clear error messages when OCR fails.
- Non-Goals:
  - Full document OCR for all PDFs regardless of parsing success.
  - Replacing existing text extraction when it works.

## Decisions
- Decision: Use AWS Textract for hosted OCR fallback extraction when text extraction yields no transactions.
- Decision: Keep the existing PdfTextExtractor as the primary path.
- Decision: Enable OCR fallback for all supported PDF parsers.
- Decision: Surface OCR failures as parsing errors during preview/import.

## Risks / Trade-offs
- OCR quality may vary based on PDF quality and fonts.
- OCR adds processing time and system dependencies.

## Migration Plan
- Add a hosted OCR API client and configuration.
- Wire OCR fallback into PdfTextExtractor when text extraction yields no transactions.
- Update PDF parsers to opt-in to OCR fallback when text extraction yields no transactions.
- Add tests that simulate OCR fallback text extraction.

## Open Questions
- None.
