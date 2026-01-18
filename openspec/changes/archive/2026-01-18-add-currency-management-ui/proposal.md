# Change: Add UI to manage currencies

## Why
There is currently no UI to add or maintain currencies in production, which blocks managing multi-currency data.

## What Changes
- Add a management UI to list, create, and edit currency records.
- Validate currency codes and prevent duplicates.

## Impact
- Affected specs: currency-management (new)
- Affected code: management UI, currency storage and validation
