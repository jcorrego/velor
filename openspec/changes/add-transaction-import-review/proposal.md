# Change: Add Transaction Import Review Queue

## Why
Imports must be reviewed before they affect tax summaries and reporting.

## What Changes
- Create import batches with review statuses and approvals.\n- Add column mapping profiles and auto-categorization suggestions.\n- Require approval before committing transactions.

## Impact
- Affected specs: import-review-management
- Affected code: import pipeline, transaction creation, review UI
