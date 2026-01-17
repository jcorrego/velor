# Change: Add PDF Parsing Framework

## Why
PDF statements require a structured parsing pipeline that differs from CSV imports, and the current finance change should stay focused on CSV import and core workflows.

## What Changes
- Introduce a PDF parsing framework for bank statements and import previews.
- Add parser selection and normalization contracts for PDF sources.

## Impact
- Affected specs: finance-management
- Affected code: app/Services/Finance/TransactionImportService.php, app/Services/Finance/Parsers/
