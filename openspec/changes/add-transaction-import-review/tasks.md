## 1. Backend Implementation
- [x] 1.1 Create ImportBatch model with Pending/Applied/Rejected statuses
- [x] 1.2 Create migration for import_batches table with proposed_transactions JSON
- [x] 1.3 Create ImportBatchStatus enum for type safety
- [x] 1.4 Add relationships: ImportBatch -> Account, ImportBatch -> User (approver)
- [x] 1.5 Create DescriptionCategoryRule model for category mapping
- [x] 1.6 Create migration for description_category_rules table
- [x] 1.7 Add rules per jurisdiction with case-insensitive pattern matching
- [x] 1.8 Update TransactionImportService to create batches instead of direct import
- [x] 1.9 Apply description rules during import preview/matching
- [x] 1.10 Remove entity_id from TransactionCategory model
- [x] 1.11 Update categorization service to use jurisdiction-only scoping
- [x] 1.12 Update all controllers, views, tests to remove entity references
- [x] 1.13 Create seeder with Spain TGSS category rule example

## 2. User Interface
- [x] 2.1 Create ImportReviewQueue Livewire component
- [x] 2.2 Build review queue template showing pending batches
- [x] 2.3 Implement batch selection and detail view
- [x] 2.4 Add approve batch button with user/timestamp tracking
- [x] 2.5 Add reject batch with required rejection reason
- [x] 2.6 Add routes: management.import-review
- [x] 2.7 Add sidebar navigation link to Import Review
- [x] 2.8 Update import form component to create batches instead of direct import
- [x] 2.9 Add transaction list to batch review showing all proposed transactions
- [x] 2.10 Display category assignments in preview and review
- [x] 2.11 Update button text to reflect batch creation workflow
- [x] 2.12 Show success message explaining batch pending review

## 3. Category Rules Management
- [x] 3.1 Create DescriptionCategoryRules Livewire component for management
- [x] 3.2 Build UI for creating/editing/deleting category rules
- [x] 3.3 List rules per jurisdiction with pattern and category display
- [x] 3.4 Add enable/disable toggle for rules (clickable badge)
- [x] 3.5 Add routes: management.description-category-rules
- [x] 3.6 Add sidebar navigation link to Category Rules
- [x] 3.7 Implement jurisdiction selector
- [x] 3.8 Add form with pattern, category, notes, and active toggle
- [x] 3.9 Display rules in table with edit/delete actions

## 4. Testing & Quality
- [x] 4.1 Create ImportBatchReviewTest with 8 comprehensive tests
- [x] 4.2 Test batch creation, approval, rejection workflows
- [x] 4.3 Test rejection reason requirement
- [x] 4.4 Test non-pending batch state validation
- [x] 4.5 Update TransactionImportTest for batch creation instead of direct import
- [x] 4.6 Create ImportBatchFactory for test data
- [x] 4.7 All 329 tests passing
- [x] 4.8 Category rule matching tested in production
- [x] 4.9 Case-insensitive pattern matching verified working

## 5. Documentation
- [x] 5.1 Updated OpenSpec proposal with implementation details
- [x] 5.2 Documented import batch workflow
- [x] 5.3 Documented description-based category rules
- [x] 5.4 Documented transaction category simplification (removed entity_id)
- [x] 5.5 Create admin guide for managing category rules
- [x] 5.6 Create user guide for reviewing and approving batches
