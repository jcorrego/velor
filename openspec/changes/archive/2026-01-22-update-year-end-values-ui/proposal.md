# Change: Move year-end values editing into accounts and assets

## Why
The standalone Year-End Values admin UI duplicates context already available in account and asset detail views. Editing year totals where users manage accounts and assets will reduce friction and make values easier to maintain.

## What Changes
- Remove the independent Year-End Values admin UI.
- Add an edit action in Accounts and Assets views that opens a modal or inline form listing all tax years and values for that item.
- Store year-end values without `as_of_date` or `currency`, since the value is always year-end and the related account/asset already defines currency.
- Show the most recent year-end value in each account/asset summary.

## Impact
- Affected specs: finance-management
- Affected code: year-end value storage, finance accounts/assets UI, validation, and tests
