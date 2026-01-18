# Change: Add PDF Parsing UI

## Why
PDF parsing is available in the backend, but users cannot trigger or preview PDF imports from the interface. A dedicated UI flow is needed to select PDF parsers, preview results, and import safely.

## What Changes
- Add UI support for PDF import alongside existing CSV import.
- Auto-select parser and expected file type based on the account (no format selector).
- Support PDF preview/confirmation only for Santander and Bancolombia; Mercury remains CSV-only.
- Surface PDF parsing errors without importing data.

## Impact
- Affected specs: finance-management
- Affected code: finance import views, Livewire import components, import validation and preview endpoints
