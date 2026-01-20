# Change: Apply Category Rules to Existing Transactions

## Why
Category rules currently affect only new transactions, which makes it hard to correct historical data without manual edits.

## What Changes
- Add an admin UI action on each category rule to preview existing matching transactions with a different category.
- Allow applying the category change to individual previewed transactions or all at once.

## Impact
- Affected specs: tax-mapping-rules
- Affected code: category rule admin UI, rule preview/apply endpoints, transaction updates
