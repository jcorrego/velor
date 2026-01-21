# Change: Add Form 5472 guidance and data capture

## Why
Form 5472 requires user guidance and non-transaction data (shareholder/related-party details) that are not captured by transaction mapping. Users also need filing due dates per year.

## What Changes
- Add Form 5472 help, instructions, and section guidance sourced from resources/forms/5472/contents.md.
- Add a structured way to capture and store Form 5472 data not derived from transactions, with per-year information.
- Add due date tracking for filings created for a specific tax year and form type.

## Impact
- Affected specs: us-tax-reporting, tax-year-filing
- Affected code: filings data model, Form 5472 reporting UI, form content storage, tests