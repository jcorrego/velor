## REMOVED Requirements
### Requirement: Related-Party Ledger
**Reason**: Related-party transactions are now tracked using the main transactions table with Form 5472 category mappings instead of a separate ledger.

**Migration**: Existing related-party transactions will be migrated to the main transactions table with appropriate Form 5472 category mappings. The functionality is preserved but implemented through the standard transaction categorization system.

## MODIFIED Requirements
### Requirement: Transaction Categories
The system SHALL allow users to create custom global financial categories for tax form mapping.

- Users MUST be able to create global categories (e.g., "Consulting Income", "Rental Income", "Office Expenses")
- Categories MUST be global (not jurisdiction-specific)
- Categories MUST have: name, income_or_expense flag
- Categories MUST be unique by name (no duplicates)
- Categories MUST support ordering/prioritization for reporting
- The same category MAY be used for transactions across multiple jurisdictions
- Jurisdiction-specific tax treatment is handled via category_tax_mappings, not by category itself

#### Scenario: Global categories with multi-jurisdiction usage
- **WHEN** user creates category "Consulting Income" (Income)
- **THEN** this category can be used for transactions in USA, Spain, and Colombia accounts
- **AND** each jurisdiction's tax treatment is defined via separate category_tax_mappings
- **AND** system prevents creating duplicate "Consulting Income" category

### Requirement: Tax Concept Mapping
The system SHALL enable mapping global categories to tax form line items for automatic tax reporting computation, including Form 5472 related-party transactions.

- The system MUST allow mapping categories to tax form line items
- Each mapping MUST specify: category, tax_form (e.g., "Schedule E", "IRPF", "Form 5472"), line_item, country
- Multiple tax form mappings MUST be supported per category (e.g., "Consulting Income" can map to Schedule C for USA and IRPF for Spain)
- Mappings MUST be user-configurable for custom tax scenarios
- Tax concept mappings MUST enable automatic computation of tax form schedule amounts
- Form 5472 related-party classifications MUST be identified via the line_item field in mappings (owner_contribution, owner_draw, personal_spending, reimbursement)
- The line_item field describes HOW a transaction is classified for tax reporting, not WHAT the transaction is

#### Scenario: Category mapped to Schedule E and IRPF
- **WHEN** "SPA_Rental_Income" maps to: Schedule E Part I Line 1 (USA) AND IRPF rental income section (Spain)
- **THEN** total rental income appears in both forms in appropriate currency/format
- **AND** Schedule E shows USD equivalent (converted from EUR), IRPF shows EUR amount
- **AND** system prevents duplicate mappings for same category/form/line combo

#### Scenario: Form 5472 owner contribution via line_item
- **WHEN** user creates a "Consulting Income" transaction for USA account
- **AND** "Consulting Income" category is mapped to Form 5472 with line_item='owner_contribution'
- **THEN** UsTaxReportingService can query this transaction as an owner contribution via the line_item='owner_contribution' mapping
- **AND** the same "Consulting Income" category MAY also be mapped to Schedule C for business income reporting
- **AND** Form 5472 shows it as owner contribution, Schedule C shows it as consulting revenue

#### Scenario: Multiple transactions with same category, different Form 5472 classifications
- **WHEN** user has two "Consulting Income" transactions
- **AND** one is mapped to Form 5472 with line_item='owner_contribution' (money going INTO entity)
- **AND** another "Consulting Income" transaction is mapped with line_item='reimbursement' (money going OUT of entity)
- **THEN** UsTaxReportingService can distinguish them by the line_item value in the mapping
- **AND** aggregate them separately for Form 5472 reporting

## ADDED Requirements
### Requirement: Form 5472 Mapping via Line Items
The system SHALL support Form 5472 related-party transaction reporting by using line_item values in category tax mappings to classify transactions.

- ANY transaction category MAY be mapped to Form 5472 with tax_form_code='form_5472'
- The line_item field in the mapping determines the Form 5472 classification: 'owner_contribution', 'owner_draw', 'personal_spending', 'reimbursement'
- Users MUST be able to map their own categories (e.g., "Consulting Income", "Personal Expenses") to Form 5472 with appropriate line_item values
- The same category MAY have multiple Form 5472 mappings with different line_item values (e.g., different transactions categorized as "Consulting Income" but reported differently)
- Transactions with Form 5472 mappings MUST be queryable by joining transactions -> categories -> category_tax_mappings
- Reporting queries MUST filter by tax_form_code='form_5472' and specific line_item values

#### Scenario: Map existing category to Form 5472
- **WHEN** user has a "Consulting Income" category
- **AND** creates a CategoryTaxMapping with tax_form_code='form_5472' and line_item='owner_contribution'
- **THEN** all "Consulting Income" transactions are available for Form 5472 owner contribution reporting
- **AND** the same transactions MAY also appear in Schedule C if another mapping exists

#### Scenario: Query transactions for Form 5472 reporting
- **WHEN** UsTaxReportingService generates Form 5472 report for tax year 2024
- **THEN** it SHALL query transactions via joins with category_tax_mappings where tax_form_code='form_5472'
- **AND** filter by specific line_item values to get owner contributions, draws, personal spending, and reimbursements
- **AND** aggregate amounts converted to USD for reporting
