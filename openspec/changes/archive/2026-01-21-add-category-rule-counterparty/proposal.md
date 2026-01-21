# Change: Add Counterparty to Category Rules

## Why
Category rules should optionally set a counterparty to reduce manual edits when categorizing transactions.

## What Changes
- Add an optional counterparty field to category rules.
- Apply the counterparty when a rule is used, including preview/apply for existing transactions.

## Impact
- Affected specs: tax-mapping-rules
- Affected code: category rules UI, rule application logic, transaction updates
