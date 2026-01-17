# tax-form-mapping Specification

## Purpose
TBD - created by archiving change add-finance-module. Update Purpose after archive.
## Requirements
### Requirement: Category to Tax Form Mapping
The system SHALL enable mapping categories to tax form line items with support for multiple forms per category.

- The system MUST allow mapping user-defined categories to tax form line items
- Each mapping MUST specify: category, tax_form_code (e.g., "Schedule_E", "Form_5472"), line_item_number, jurisdiction
- Multiple tax forms MUST be supported per category (income affects income tax + self-employment tax)
- Mappings MUST be user-configurable and jurisdiction-specific
- The system MUST prevent duplicate mappings (unique per category + tax_form + line_item)

#### Scenario: Category mapped to multiple tax forms
- **WHEN** category "US_LLC_Dev_Income" maps to: Form 1040 Schedule C Line 1 AND Form 1040 Schedule SE Line 2
- **THEN** total "US_LLC_Dev_Income" transactions (100,000 USD) flows to both forms
- **AND** Schedule C shows $100,000 income, Schedule SE uses it for self-employment tax
- **AND** system prevents duplicate mappings for same category/form/line combo

### Requirement: Supported Tax Forms
The system SHALL support mappings for all major USA, Spain, and Colombia tax forms.

- **USA Forms**: Form 1040, Schedule C, Schedule E, Schedule SE, Form 1065, Form 5472, Form 1120
- **Spain Forms**: IRPF (Personal Income Tax), Impuesto sobre Sociedades (Corporate Income Tax), IVA (Value Added Tax)
- **Colombia Forms**: Impuesto sobre la Renta (Income Tax), Retención en la Fuente (Withholding Tax), IVA (Value Added Tax)

#### Scenario: Jurisdiction-specific tax forms
- **WHEN** user has USA entity categories mapping to Form 1040, Schedule C, Form 5472
- **AND** Spain profile categories mapping to IRPF personal income sections
- **AND** Colombia profile categories mapping to Colombian income tax sections
- **THEN** same user can have all three with different tax form mappings per entity

### Requirement: Category Aggregation and Reporting
The system SHALL aggregate transactions by category and compute tax form line item amounts automatically.

- Given a tax year and jurisdiction, system MUST:
  1. Find all categories associated with that jurisdiction
  2. Sum all transactions in each category (converted to base currency via FX rates)
  3. Apply category → tax form mappings
  4. Aggregate by tax form line item
  5. Return tax form amounts
- Reporting MUST exclude categories without explicit mappings (flagged as unmapped)

#### Scenario: Computing Schedule E rental income from transactions
- **WHEN** tax year 2024, USA jurisdiction
- **AND** categories: "US_Rental_Income" (mapped to Schedule E Line 1), "US_Rental_Expenses" (mapped to Schedule E Line 18)
- **AND** transactions: US_Rental_Income = [12,000 USD, 15,000 USD, 8,000 USD]; US_Rental_Expenses = [3,000 USD, 2,500 USD, 1,200 USD]
- **THEN** system computes: Schedule E Line 1 = 35,000 USD; Schedule E Line 18 = 6,700 USD
- **AND** unmapped categories excluded from Schedule E

### Requirement: Multi-Currency Normalization
The system SHALL convert all aggregated amounts to user base currency using historical FX rates.

- All aggregated amounts MUST be converted to the user's base currency
- Conversion MUST use FX rates from the transaction date
- Conversion MUST be visible in reporting (show currency and rate used)
- Jurisdiction-specific amounts MUST use that jurisdiction's base currency if different from user base currency

#### Scenario: Multi-currency Schedule E with conversions
- **WHEN** reporting Schedule E for 2024 tax year
- **AND** Florida rental (USD): $12,000; Medellín rental (COP): 20,000,000 COP; EUR account rental: 5,000 EUR
- **AND** conversions: COP 20M at 4,040 COP/USD = $4,950; EUR 5k at 1.08 EUR/USD = $5,400
- **THEN** Schedule E Line 1 total: $12,000 + $4,950 + $5,400 = $22,350 (all USD)
- **AND** report shows: "Converted 3 currencies to base currency USD using historical rates"

### Requirement: Jurisdiction-Specific Tax Mappings
The system SHALL support jurisdiction-specific mappings for equivalent categories across countries.

- Spain-based income categories MUST map to IRPF sections
- USA-based income categories MUST map to Form 1040 sections and relevant schedules
- Colombia-based income categories MUST map to Colombian income tax sections
- The system MUST support categories spanning multiple jurisdictions (e.g., "Global_Business_Expenses")

#### Scenario: Global category with jurisdiction-specific mappings
- **WHEN** category "Global_Professional_Expenses" applies to all entities
- **AND** mappings: USA → Schedule C Line 27; Spain → IRPF Gastos Deducibles; Colombia → Colombian deductible expenses section
- **THEN** single category, same transactions, maps differently per jurisdiction
- **AND** each filing pulls only relevant jurisdiction amounts

### Requirement: Related-Party Transaction Tax Mapping
The system SHALL map related-party transactions to Form 5472 for compliance tracking.

- Form 5472 related-party transactions MUST be separately mappable
- Each related-party transaction type (Owner Contribution, Owner Draw, etc.) MUST map to Form 5472 transaction type codes
- Form 5472 Schedule D (Transaction Detail) MUST auto-populate from related-party transactions
- Form 5472 amounts MUST be in USD (no currency conversion)

#### Scenario: Form 5472 auto-population from related-party ledger
- **WHEN** related-party transaction: $50,000 owner contribution on 2024-02-01 (Owner Contribution type)
- **AND** related-party transaction: $30,000 owner draw on 2024-06-15 (Owner Draw type)
- **THEN** both transactions mapped to Form 5472 Part II (Transaction Detail)
- **AND** Form 5472 Line 6 (owner contribution): $50,000
- **AND** Form 5472 Line 11 (owner draw): $30,000
- **AND** system auto-populates Schedule D from related-party ledger

### Requirement: Category Configuration and Validation
The system SHALL validate category mappings and warn about unmapped categories.

- Users MUST be able to view all categories and their current tax mappings
- Users MUST be able to add/update/delete category mappings
- The system MUST validate: tax form codes, line item numbers, jurisdictions, category existence
- The system MUST warn if a category has no mappings (transactions in this category won't be tax-reportable)

#### Scenario: User validation when adding mapping
- **WHEN** user creates category "US_Consulting_Income" and attempts to map to "Form 9999" (non-existent)
- **THEN** system rejects "Unsupported tax form"
- **AND** when attempting Schedule C Line 50 (max is Line 27), system rejects "Invalid line item"
- **AND** when attempting Spain IRPF (jurisdiction mismatch), system warns "Category is USA, mapping is Spain"
- **AND** successfully maps to Schedule C Line 1 with confirmation

### Requirement: Multi-Filing Scenario Support
The system SHALL support multiple filings per tax year with form-specific transaction filtering.

- A user may have multiple filings for one tax year (e.g., Form 5472 + Form 1120 for USA LLC)
- Each filing MAY reference specific tax form mappings (e.g., Form 5472 only pulls related-party transactions)
- Tax form amounts MUST be filterable by filing (some amounts appear in multiple filings, some in one)

#### Scenario: Multiple filings for same tax year
- **WHEN** USA LLC, 2024 tax year with filings: Form 5472 (shareholder info), Form 1120 (LLC income tax), Form 1065 K-1 (Partnership distribution)
- **THEN** Form 5472 pulls related-party transactions only
- **AND** Form 1120 pulls all transactions except related-party
- **AND** Form 1065 K-1 pulls only distribution transactions
- **AND** system segregates transaction mappings per filing to avoid double-counting

