# Specification: Finance Management

## Overview
Finance management provides the core data structures and operations for tracking accounts, assets, and transactions across multiple currencies and jurisdictions.

## ADDED Requirements

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
- Each account MUST support optional integration metadata: CSV import format, API provider (future), last sync date
- Each account MUST have opening_date and optional closing_date
- Accounts MUST be unique per entity (user cannot create duplicate accounts for same bank)
- The system SHALL allow CSV/PDF upload for transaction imports with credentials stored encrypted (for future API sync)

#### Scenario: Multiple accounts across jurisdictions with checking and savings
- **WHEN** user creates accounts: Banco Santander checking (EUR, Spain), Bancolombia savings (COP, Colombia), Mercury checking (USD, USA)
- **THEN** each account SHALL be linked to respective jurisdiction and account type
- **AND** system SHALL prevent creating second Banco Santander checking account in same entity
- **AND** integration metadata (CSV format signature, last uploaded_at) SHALL be stored for future API sync
- **AND** savings accounts tracked separately for interest income reconciliation

### Requirement: Asset Management
The system SHALL track real estate assets with jurisdiction, ownership structure, acquisition cost, and current valuations for tax reporting.

- Users MUST be able to track real estate assets with jurisdiction, acquisition cost, and valuation
- Each asset MUST track: name, type (Residential, Commercial, Land), jurisdiction, ownership structure (Individual/LLC/Partnership)
- Each asset MUST have: acquisition_date, acquisition_cost_in_currency, acquisition_currency
- Each asset MUST support current_valuation tracking: amount, valuation_date, valuation_method (Appraisal, Market Comparable, Tax Assessed)
- Assets MAY support depreciation: method (Straight-line), useful_life_years, annual_depreciation_amount
- Assets MUST link to transactions tagged as rental income or property expenses
- Asset valuations MUST be time-tracked (historical valuations for fair market value determination)

#### Scenario: Real estate with multiple valuations
- **WHEN** user owns Florida apartment (Residential, Miami FL) acquired 2019-03-15 for USD 250,000
- **AND** 2023 appraisal: USD 320,000; 2024 market comparable: USD 335,000
- **THEN** property has multiple historical valuations in original currency
- **AND** annual depreciation: Straight-line, 27.5 years = ~USD 9,091/year
- **AND** converted to EUR for Spanish tax reporting

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

## Data Integrity
- Account names MUST be non-empty and unique per entity
- Currencies MUST be valid ISO 4217 codes
- FX rates MUST be positive numbers
- Asset acquisition dates MUST be valid dates
- Transaction amounts MUST be non-negative
- Category names MUST be non-empty and unique per (jurisdiction, entity) pair
- Related-party amounts MUST be non-negative

## Error Handling
- Creating a transaction with an unsupported currency SHALL raise CurrencyNotSupportedException
- Creating a duplicate account SHALL raise DuplicateAccountException
- Referencing a non-existent category SHALL raise CategoryNotFoundException
- Creating a duplicate related-party transaction SHALL raise DuplicateRelatedPartyTransactionException
