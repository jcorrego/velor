# Change: Add Transaction Import Review Queue

## Why
Imports must be reviewed before they affect tax summaries and reporting. Users need visibility into what transactions will be imported and the ability to catch errors before they impact tax calculations.

## What Changes
- Create import batches that store proposed transactions pending review
- Build review queue UI where users can approve or reject batches
- Add description-based category rules for automatic transaction categorization
- Require user approval before committing imported transactions to the account
- Auto-assign categories during import based on description patterns

## Implementation Details

### Import Batch Workflow
1. User uploads CSV/PDF file via import form
2. File is parsed and matched against existing transactions
3. New transactions stored in ImportBatch with status Pending
4. User navigates to Import Review queue
5. User reviews batch details and can Approve or Reject
6. On Approve: transactions created with auto-assigned categories
7. On Reject: batch marked as rejected with reason, no transactions created

### Description-Based Category Rules
- Administrators create rules per jurisdiction
- Each rule maps a description pattern to a transaction category
- Rules use case-insensitive matching on description start
- Applied automatically during transaction creation from approved batches
- Example: Pattern STARBUCKS → Category Coffee Expenses

## Impact
- Affected specs: import-review-management
- Affected code: import pipeline, transaction creation, review UI, categorization service
- Affected models: ImportBatch, DescriptionCategoryRule
- Affected routes: management.import-review
