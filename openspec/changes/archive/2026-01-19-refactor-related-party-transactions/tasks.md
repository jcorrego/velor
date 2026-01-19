## 1. Data Migration
- [ ] 1.1 Create migration to remove jurisdiction_id from transaction_categories table
- [ ] 1.2 Update unique constraint on transaction_categories from [jurisdiction_id, name] to [name]
- [ ] 1.3 Consolidate duplicate categories across jurisdictions (e.g., merge jurisdiction-specific categories into global ones)
- [ ] 1.4 Update existing category_tax_mappings to ensure proper jurisdiction-specific reporting
- [ ] 1.5 Migrate existing related_party_transactions to transactions table with appropriate categories
- [ ] 1.6 Create category_tax_mappings for Form 5472 with appropriate line_item values
- [ ] 1.7 Create migration to drop related_party_transactions table

## 2. Model and Enum Updates
- [ ] 2.1 Remove RelatedPartyTransaction model
- [ ] 2.2 Remove RelatedPartyType enum
- [ ] 2.3 Remove RelatedPartyTransactionFactory
- [ ] 2.4 Update TransactionCategory model to remove jurisdiction relationship
- [ ] 2.5 Update TransactionCategoryFactory to remove jurisdiction_id

## 3. Service Layer Updates
- [ ] 3.1 Update UsTaxReportingService::getOwnerFlowSummary to query transactions via category tax mappings
- [ ] 3.2 Update helper methods to work with Transaction model instead of RelatedPartyTransaction
- [ ] 3.3 Remove convertRelatedPartyTransactionsToUSD method or merge with convertTransactionsToUSD

## 4. HTTP Layer Updates
- [ ] 4.1 Remove or repurpose StoreRelatedPartyTransactionRequest
- [ ] 4.2 Update any controllers that handle related-party transactions to use regular transaction endpoints
- [ ] 4.3 Update routes if needed

## 5. Test Updates
- [ ] 5.1 Update UsTaxReportingServiceTest to use transactions with Form 5472 category mappings
- [ ] 5.2 Update StoreRelatedPartyTransactionRequestTest or remove if not repurposed
- [ ] 5.3 Update ModelTest for RelatedPartyTransaction references
- [ ] 5.4 Update any feature tests that create related-party transactions

## 6. Seeder Updates
- [ ] 6.1 Update DatabaseSeeder to create Form 5472 categories and mappings
- [ ] 6.2 Update DatabaseSeeder to seed related-party transactions as regular transactions

## 7. Validation and Cleanup
- [ ] 7.1 Run all tests to ensure nothing is broken
- [ ] 7.2 Verify Form 5472 reporting still works correctly
- [ ] 7.3 Run pint to ensure code style compliance
- [ ] 7.4 Search codebase for any remaining references to RelatedPartyTransaction or RelatedPartyType
