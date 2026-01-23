# finance-management Specification

## Purpose
TBD - created by archiving change add-finance-module. Update Purpose after archive.
## Requirements
### Requirement: Currency Management
The system SHALL support multiple currencies and allow users to configure preferred currencies for accounts and reporting.

- The system SHALL support multiple currencies (USD, EUR, COP, GBP, JPY, etc.)
- The system SHALL store a primary currency for each user (EUR for Spain-based users)
- The system SHALL store a native currency for each account
- The system SHALL provide currency codes (ISO 4217) and display names (USD = "US Dollar")
- The system SHALL prevent transactions in unsupported currencies

#### Scenario: User with EUR base currency and multi-currency accounts
- **WHEN** user (base currency EUR) has USD account in USA and COP account in Colombia
- **AND** creates transaction of 1,000 USD in USA account on 2024-01-15
- **THEN** the system SHALL fetch ECB rate USD/EUR for 2024-01-15 and convert
- **AND** store original amount (1,000 USD) and converted amount (922 EUR at 0.922 rate)
- **AND** all tax reporting done in EUR base currency

### Requirement: Account Management
The system SHALL allow users to create and manage multiple financial accounts across different banks, currencies, and jurisdictions, supporting checking and savings account types.

- Users MUST be able to create multiple financial accounts (checking, savings, digital wallet)
- Each account MUST have: name, type (Checking, Savings, Digital), native currency, entity ownership
- Each account MUST be associated with a single entity (Personal profile)
- Each account MUST support optional integration metadata: CSV import format, last import date
- Each account MUST have opening_date and optional closing_date
- Accounts MUST be unique per entity (user cannot create duplicate accounts for same bank)
- The system SHALL allow CSV/PDF upload for transaction imports

#### Scenario: Multiple accounts across jurisdictions with checking and savings
- **WHEN** user creates accounts: Banco Santander checking (EUR, Spain), Bancolombia savings (COP, Colombia), USA checking (USD, USA)
- **THEN** each account SHALL be linked to respective jurisdiction and account type
- **AND** system SHALL prevent creating second Banco Santander checking account in same entity
- **AND** integration metadata (CSV format signature, last uploaded_at) SHALL be stored for import tracking
- **AND** savings accounts tracked separately for interest income reconciliation

### Requirement: Transaction Tracking
The system SHALL record financial transactions with currency conversion, reconciliation, and category linking for tax mapping, supporting CSV/PDF import.

- Users MUST be able to record financial transactions: date, amount, account, type, description
- Users MUST be able to import transactions from CSV or PDF bank statements
- Each transaction MUST record: date, original_amount, original_currency, account, type (Income, Expense, Transfer, Fee)
- Each transaction MUST support: counterparty name/description, category (user-defined), tags (user-defined labels)
- Each transaction MUST be converted to EUR (user's base currency) using FX rates
- Each transaction MUST track: converted_amount, fx_rate_used, fx_source (ECB, Manual Override)
- Transactions MUST support reconciliation: reconciled_at timestamp, reconciliation status
- Transactions MUST link to a category for tax mapping
- Transactions MUST NOT have negative IDs; all amounts stored as positive with type indicating direction

#### Scenario: Multi-currency transaction with FX conversion
- **WHEN** user receives 1,000 USD from property rental on 2024-01-15 in USA account
- **AND** category "SPA_Rental_Income", account "Mercury"
- **THEN** FX rate (USD/EUR) from ECB: 0.922 = 922 EUR converted
- **AND** user can override rate to 0.925 (bank rate) = 925 EUR
- **AND** transaction shows both original and override rates in reporting

### Requirement: Transaction Categories
The system SHALL allow users to create custom financial categories tied to jurisdictions and entities for tax form mapping, focused on rental income reporting.

- Users MUST be able to create custom categories (e.g., "SPA_Rental_Income", "SPA_Rental_Expenses", "SPA_Personal_Employment")
- Categories MUST be jurisdiction-specific (Spain, USA, Colombia)
- Categories MUST be entity-specific (Personal, LLC-USA)
- Categories MUST have: name, jurisdiction, entity, income_or_expense flag
- Categories MUST be used to classify transactions and link to tax forms
- The system MUST prevent duplicate categories (unique per jurisdiction + entity + name)
- Categories MUST support ordering/prioritization for reporting

#### Scenario: Rental income and expense categories
- **WHEN** user creates categories: "SPA_Rental_Income" (Income), "SPA_Rental_Expenses" (Expense), "SPA_Personal_Employment" (Income)
- **THEN** rental income/expenses map to Schedule E Part I (rental property reporting)
- **AND** personal employment maps to IRPF employment income section
- **AND** system prevents duplicate "SPA_Rental_Income" in same entity/jurisdiction

### Requirement: Tax Concept Mapping
The system SHALL enable mapping categories to tax form line items for automatic tax reporting computation, focused on Schedule E rental reporting.

- The system MUST allow mapping categories to tax form line items
- Each mapping MUST specify: category, tax_form (e.g., "Schedule E", "IRPF"), line_item, country
- Multiple tax form mappings MUST be supported per category (e.g., rental expenses affect multiple sections)
- Mappings MUST be user-configurable for custom tax scenarios
- Tax concept mappings MUST enable automatic computation of tax form schedule amounts

#### Scenario: Category mapped to Schedule E and IRPF
- **WHEN** "SPA_Rental_Income" maps to: Schedule E Part I Line 1 (USA) AND IRPF rental income section (Spain)
- **THEN** total rental income appears in both forms in appropriate currency/format
- **AND** Schedule E shows USD equivalent (converted from EUR), IRPF shows EUR amount
- **AND** system prevents duplicate mappings for same category/form/line combo

### Requirement: Related-Party Ledger
The system SHALL track owner-to-entity transactions separately with audit trail for compliance tracking.

- The system MUST track owner-to-entity transactions separately from regular transactions
- Related-party transactions MUST record: date, amount, type (Owner Contribution, Owner Draw, Personal Spending, Reimbursement)
- Related-party transactions MUST link to: related_party (owner user), related_account (entity account), related_document (bank transfer reference)
- Related-party transactions MUST track reconciliation (reconciled_at, verified_at)
- The system MUST prevent duplicate related-party transactions (unique per date + amount + type + account pair)
- Related-party transactions MUST link to tax filing for audit trail

#### Scenario: Owner contribution and draw tracking
- **WHEN** user contributes EUR 10,000 to entity account on 2024-02-01 (Owner Contribution type)
- **AND** takes EUR 5,000 draw on 2024-06-15 (Owner Draw type)
- **THEN** both transactions linked to entity bank account (Banco Santander)
- **AND** tax filing auto-populated with these transactions for audit trail
- **AND** all amounts in EUR; reconciled against bank statements

### Requirement: Multi-Jurisdiction Currency Normalization
The system SHALL normalize multi-currency transactions to EUR base currency for accurate Spanish tax reporting.

- Transactions MUST be auto-converted to the account's native currency at creation
- Transactions MUST be auto-converted to EUR (user's base currency) for reporting
- Category-level reporting MUST aggregate transactions by jurisdiction in EUR
- All tax reporting MUST use EUR as base currency for Spanish tax compliance

#### Scenario: Multi-currency reporting with currency normalization
- **WHEN** reporting rental income for 2024 tax year
- **AND** Florida property (USD): 12,000 USD; Colombia property (COP): 20,000,000 COP at 4,040 COP/USD = USD 4,950
- **AND** convert both to EUR: USD 12,000 at 0.922 EUR/USD = EUR 11,064; USD 4,950 at 0.922 EUR/USD = EUR 4,564
- **THEN** total rental income in EUR: 11,064 + 4,564 = EUR 15,628 (all in EUR base currency)
- **AND** Schedule E shows USD amounts, IRPF shows EUR amounts

### Requirement: Manual Transaction Entry and Editing
The system SHALL allow users to manually create and edit transactions from the Finance UI.

#### Scenario: Manual transaction creation
- **WHEN** a user enters a transaction with date, account, type, amount, currency, and description
- **THEN** the system SHALL create the transaction and show it in the transaction list
- **AND** the system SHALL validate required fields and ownership

#### Scenario: Access manual transaction form
- **WHEN** a user clicks the Add transaction button in the Finance UI
- **THEN** the system SHALL open the manual transaction form

#### Scenario: Manual transaction editing
- **WHEN** a user edits an existing transaction they own
- **THEN** the system SHALL persist the changes and update the transaction list
- **AND** the system SHALL prevent edits to transactions owned by other users

### Requirement: PDF Statement Parsing Framework
The system SHALL provide a PDF parsing framework to normalize bank statement PDFs into transaction data for import workflows.

#### Scenario: Bank PDF parsed into normalized transactions
- **WHEN** a user uploads a bank statement PDF with a supported parser
- **THEN** the system SHALL extract transaction rows into a normalized transaction array
- **AND** the system SHALL surface parsing errors without importing data

#### Scenario: OCR fallback when PDF has no text rows
- **WHEN** a user uploads a bank statement or legal document PDF and text extraction yields no transaction rows
- **AND** the selected parser supports OCR fallback
- **THEN** the system SHALL attempt OCR-based text extraction for the statement pages
- **AND** the system SHALL parse transactions from the OCR output if present
- **AND** the system SHALL surface OCR-related parsing errors without importing data

### Requirement: PDF Parsing UI
The system SHALL provide a UI to upload bank statement PDFs, auto-select a supported parser based on the account, and preview parsed transactions before importing.

#### Scenario: Preview PDF import
- **WHEN** a user views the import UI for a supported account
- **AND** uploads a PDF file
- **THEN** the system SHALL display a preview of parsed transactions
- **AND** allow the user to confirm or cancel the import

#### Scenario: PDF parsing errors
- **WHEN** a PDF parser fails to extract transactions
- **THEN** the system SHALL show an error message
- **AND** no transactions SHALL be imported

#### Scenario: Account-based parser selection
- **WHEN** the account is Banco Santander or Bancolombia
- **THEN** the UI SHALL expect a PDF statement file and use the matching PDF parser
- **AND** Mercury accounts SHALL require CSV uploads only

### Requirement: Finance Section Sidebar Navigation
The system SHALL expose Finance sections as independent links in the left sidebar, alongside existing Finance form links, and SHALL remove the tab-based navigation within the Finance page for these sections, and the Finance page and link itself should also be removed.

#### Scenario: Navigate to a Finance section from the sidebar
- **WHEN** a user opens the left sidebar
- **THEN** links for Accounts, Transactions, Assets, Categories, and Mappings SHALL be visible alongside existing Finance form links
- **AND** selecting any of those links SHALL navigate directly to the corresponding Finance section

### Requirement: Year-End Asset and Account Values
The system SHALL allow users to manually enter and edit year-end values for assets and accounts directly from the related Accounts and Assets UIs, scoped to a specific entity and tax year, and SHALL compute total assets per entity and tax year from those values.

- The system SHALL provide an edit action from each account and asset view that opens a modal or inline form to manage year-end values by tax year.
- The system SHALL store year-end values without `as_of_date` or `currency`; the value is implicitly the year-end value and uses the related account/asset currency.
- The system SHALL show the most recent year-end value in the account and asset summary views.

#### Scenario: Edit year-end values from an account view
- **WHEN** a user opens an account and selects the year-end values edit action
- **THEN** the system SHALL show a list of all tax years with editable year-end values for that account
- **AND** saving changes SHALL update the values for the selected tax years

#### Scenario: Edit year-end values from an asset view
- **WHEN** a user opens an asset and selects the year-end values edit action
- **THEN** the system SHALL show a list of all tax years with editable year-end values for that asset
- **AND** saving changes SHALL update the values for the selected tax years

#### Scenario: Display latest year-end value in summary
- **WHEN** a user views an account or asset summary
- **THEN** the system SHALL display the most recent year-end value for that item, if one exists

### Requirement: Asset Management
The system SHALL track assets with ownership and acquisition details, deriving jurisdiction from the owning entity, and supporting optional address association.

- Assets MUST inherit jurisdiction from the owning entity; assets SHALL NOT store a separate jurisdiction.
- Assets MAY reference a saved address for location details.
- Asset creation/editing MUST allow selecting an existing address or creating a new address via a modal form.

#### Scenario: Asset inherits jurisdiction from entity
- **WHEN** a user creates or edits an asset
- **THEN** the asset SHALL use the entity’s jurisdiction for reporting and display
- **AND** the asset SHALL NOT store a separate jurisdiction value

#### Scenario: Associate an existing address to an asset
- **WHEN** a user selects a saved address while editing an asset
- **THEN** the asset SHALL store the address association

#### Scenario: Create an address while editing an asset
- **WHEN** a user creates a new address from the asset form
- **THEN** the system SHALL save the address and associate it to the asset
- **AND** the address form SHALL appear in a modal

