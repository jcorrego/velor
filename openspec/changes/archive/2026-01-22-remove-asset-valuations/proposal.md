# Change: Remove asset valuations

## Why
Asset valuations duplicate the new Year-End Values workflow and create two sources of truth for asset totals. We want a single, manual year-end snapshot for reporting and tax summaries.

## What Changes
- **BREAKING** Remove the Asset Valuation model, endpoints, and related UI/resource paths.
- Use Year-End Values as the sole source for asset/account year-end reporting.
- Update Spain tax reporting asset totals to read from Year-End Values instead of valuations.

## Impact
- Affected specs: finance-management, spain-tax-reporting
- Affected code: asset valuation model/factory/resources/controllers/routes; SpainTaxReportingService; tests covering asset valuations
