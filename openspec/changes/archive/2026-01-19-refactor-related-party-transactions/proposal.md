# Change: Consolidate Related-Party Transactions Using Tax Category Mappings

## Why
Currently, related-party transactions (owner contributions, owner draws, personal spending, reimbursements) are stored in a separate `related_party_transactions` table, creating data duplication and complexity. The same imported bank transactions are being tracked in two places - the main `transactions` table and the `related_party_transactions` table. This separation makes it difficult to maintain a single source of truth and creates unnecessary complexity when generating tax reports like Form 5472.

## What Changes
- **BREAKING**: Remove the `related_party_transactions` table and `RelatedPartyTransaction` model
- **BREAKING**: Make transaction categories global by removing `jurisdiction_id` foreign key from `transaction_categories` table
- **BREAKING**: Change unique constraint on `transaction_categories` from `[jurisdiction_id, name]` to just `[name]`
- Leverage the existing `category_tax_mappings` system to identify related-party transaction types
- Categories describe WHAT the transaction is (e.g., "Consulting Income", "Rental Income", "Office Expenses")
- Tax mappings with `line_item` field describe HOW it's reported on Form 5472 (e.g., "owner_contribution", "owner_draw", "personal_spending")
- Example: A "Consulting Income" transaction can be mapped to Form 5472 with line_item='owner_contribution' for US reporting
- Update `UsTaxReportingService` to query transactions by category tax mappings (tax_form_code='form_5472' and specific line_item) instead of querying the separate related-party table
- Migrate existing related-party transaction data into the main transactions table with appropriate categories
- Migrate existing jurisdiction-specific categories to global categories (consolidate duplicates)
- Update all references, tests, factories, and seeders
- Remove `RelatedPartyType` enum (replaced by category tax mapping line items)

## Impact
- Affected specs: finance-management, us-tax-reporting
- Affected code:
  - Database: `related_party_transactions` table (removed), `transaction_categories` table (modified - remove jurisdiction_id, change unique constraint)
  - Models: `RelatedPartyTransaction` (removed), `TransactionCategory` (modified - remove jurisdiction relationship)
  - Enums: `RelatedPartyType` (removed)
  - Services: `UsTaxReportingService` (modified to query via category tax mappings)
  - Tests: All tests referencing `RelatedPartyTransaction`, `RelatedPartyType`, and jurisdiction-specific categories
  - Factories: `RelatedPartyTransactionFactory` (removed), `TransactionCategoryFactory` (modified)
  - Seeders: `DatabaseSeeder` (modified)
  - HTTP: `StoreRelatedPartyTransactionRequest` (removed or repurposed for regular transactions)
- Migration strategy: Data migration required to:
  1. Make transaction categories global (remove jurisdiction_id)
  2. Consolidate duplicate categories across jurisdictions (e.g., merge "SPA_Rental_Income" and "USA_Rental_Income" into "Rental Income")
  3. Update category tax mappings to preserve jurisdiction-specific reporting via mappings
  4. Migrate existing related-party transactions to main transactions table with appropriate categories and Form 5472 mappings
- Benefits:
  - Single source of truth for all transactions
  - Simplified data model - no separate related-party table
  - Reuses existing tax category mapping infrastructure
  - Easier to categorize imported transactions for tax reporting
  - Same transaction can be used for multiple tax forms via multiple mappings
  - More flexible - users can create their own related-party categories if needed
  - Consistent with the existing pattern used for Schedule E and other tax forms
