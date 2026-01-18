# Change: Add YNAB Sync

## Why
YNAB data should sync via a read-only integration to reduce manual imports.

## What Changes
- Add read-only YNAB sync for budgets, accounts, and transactions.\n- Map YNAB accounts to platform accounts before import.\n- Route imported data through the review queue.

## Impact
- Affected specs: ynab-integration, integration-management, import-review-management
- Affected code: YNAB API client, sync jobs, mapping UI
