# Change: Add Document Management

## Why
Tax preparation requires storing, tagging, and linking documents to data records.

## What Changes
- Add document upload and storage with metadata and tags.
- Support linking documents to entities, assets, transactions, and filings.
- Provide search and filters over document metadata.
- Enable OCR text extraction for legal documents to make content searchable using the existing OCR service.

## Impact
- Affected specs: document-management
- Affected code: document storage, tagging, linking UI
- Dependencies: OCR service from add-ocr-pdf-extraction
