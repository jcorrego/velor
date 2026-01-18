# Change: Add Mercury Sync

## Why
Mercury data should sync via a read-only integration to reduce manual imports.

## What Changes
- Add read-only Mercury sync for accounts and transactions.\n- Map Mercury accounts to platform accounts before import.\n- Route imported data through the review queue.

## Impact
- Affected specs: mercury-integration, integration-management, import-review-management
- Affected code: Mercury API client, sync jobs, mapping UI
