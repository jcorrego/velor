# Change: Add year-end asset and account values

## Why
Year-end asset and account values are required to compute total assets per entity for annual tax reporting and disclosures. Current data only tracks transactions and valuations, which does not guarantee a single, authoritative year-end snapshot.

## What Changes
- Add a manual year-end value entry feature for accounts and assets, scoped to an entity and tax year.
- Provide totals per entity and tax year based on the entered year-end values.
- Enforce validation and uniqueness rules to avoid duplicate year-end values for the same entity/tax year/item.

## Impact
- Affected specs: finance-management
- Affected code: finance data models, finance UI, reporting totals
