# Design: Finance Module

## Core Concepts

### Multi-Currency Architecture
- Base currency per user (EUR for Spanish tax reporting)
- Each account has its native currency (USD, EUR, COP, GBP, etc.)
- Transactions stored in account currency with auto-conversion to EUR base currency
- FX rates cached daily by currency pair (USD/EUR, COP/EUR, etc.)
- FX source preferences: ECB (default and primary source for EUR), per-transaction override for bank-specific rates

### Account Types
- **Checking accounts**: Banco Santander checking (EUR), Mercury checking (USD), Bancolombia checking (COP)
- **Savings accounts**: High-yield savings, fixed deposits, money market accounts
- **Digital wallets**: PayPal, Stripe, crypto platforms (future)
- **Real estate holdings**: Asset depreciation tracking
- **CSV/PDF imports**: Bank statement uploads (Santander exports, Mercury CSVs, etc.)

Account fields:
- Account name, type, currency, entity ownership (personal vs LLC)
- Integration metadata: API keys (encrypted), last sync date, next sync
- Opening/closing dates, account number (last 4 digits)

### Assets (Real Estate)
Track ownership structures: individual ownership, LLC ownership, partnership stakes

Asset fields:
- Asset name (e.g., "Florida Apartment"), type (residential, commercial, land)
- Jurisdiction (Miami FL, Medellín Colombia, Madrid Spain)
- Ownership: owner (User), structure (Individual/LLC/Partnership), ownership percentage
- Acquisition: date, cost basis, currency
- Current valuation: amount, valuation date, method (appraisal, market comparable, tax assessed)
- Depreciation: method (straight-line), useful life, annual depreciation
- Rental income: linked transactions tagged as rental income
- Expenses: linked transactions tagged as property expenses

### Transactions
Core financial unit: represents money movement in and out of accounts

Transaction fields:
- Date, amount (original currency), account, type (income, expense, transfer, fee)
- Description, counterparty (business name, person, internal transfer)
- Category (user-defined: "US_Rental_Income", "US_LLC_Dev_Income", etc.)
- Tags (free-form for grouping: #usa-rental, #payroll, etc.)
- FX conversion: original amount/currency, converted amount/rate, rate source
- Reconciliation: matched to bank statement, reconciled_at, reconciled_by
- Attachment: receipt, invoice, statement (future)

### Categories and Tax Mappings
User-defined categories bridge financial reality to tax forms

Category fields:
- Category name (user-defined, e.g., "SPA_Rental_Income", "SPA_Personal_Employment", "Property_Expenses_US")
- Jurisdiction (Spain, USA, Colombia)
- Entity (Personal, LLC-USA, etc.)
- Income/expense distinction
- Base currency for reporting (EUR for Spain)

Tax concept mappings:
- Link category → tax form/line item (e.g., "SPA_Rental_Income" → "Schedule E, Part I, Line 1")
- Support multiple forms per category (rental income + related expenses)
- EUR amount is source of truth; all reporting in EUR base currency

Examples:
- "SPA_Rental_Income" (Category) → Schedule E Part I (Form mapping) → Rental income line item
- "SPA_Personal_Employment" (Category) → IRPF (Spanish tax return) employment income section
- "Property_Expenses_US" (Category) → Schedule E Part I (Form mapping) → Property expenses line item

### Related-Party Ledger (Form 5472 Tracking)
Track owner-to-LLC and LLC-to-owner transactions separately from regular transactions

Related-party transaction fields:
- Date, amount, type: owner contribution, owner draw, personal spending, reimbursement
- LLC account (source/destination), owner account (if applicable)
- Description, related document (e.g., bank transfer ref)
- Reconciliation: matched to bank statement, verified_at

Related-party types:
- **Owner Contribution**: Cash/equity from owner to LLC (increases basis)
- **Owner Draw**: Profits/cash distribution from LLC to owner (reduces basis)
- **Personal Spending**: Personal expenses paid from LLC account (taxable implications)
- **Reimbursement**: LLC reimburses owner for business expenses (non-taxable)

## Data Flow

### Import/Sync
1. User uploads CSV or PDF statement from bank (Santander, Bancolombia, Mercury)
2. System parses transaction data: date, amount, description, counterparty
3. Create Transaction record with account + original currency
4. Auto-tag or categorize based on description matching (future: ML-based categorization)
5. Reconcile against bank statement (mark reconciled_at)
6. Future: API sync with Mercury, Plaid, OpenBanking

### FX Conversion
1. At transaction creation: fetch FX rate for (original_currency → EUR) for transaction date
2. Store rate source (ECB, manual override, etc.)
3. Compute converted amount = amount × rate
4. Allow user override: swap rate if correction needed (e.g., bank actual rate)

### Reporting
1. For tax year: sum transactions by category and jurisdiction
2. Apply category → tax form mapping
3. Generate Schedule E rental income/expense totals
4. Generate IRPF (Spanish income tax) sections with EUR amounts
5. All amounts in EUR (base reporting currency)

## Key Design Decisions

1. **Transactions are the source of truth**: All tax reporting derives from transaction data + category mappings
2. **EUR base currency**: All conversions to EUR for Spanish tax compliance; no multi-base-currency support initially
3. **CSV/PDF import first**: Manual statement upload is primary entry point; API sync deferred to Phase 2
4. **FX rates cached**: Avoid repeated ECB API calls; store daily rates in DB
5. **Multi-level categorization**: User categories → Tax concept mappings allows flexibility
6. **Encrypted credentials**: API keys for future account sync stored encrypted in DB
7. **Asset valuations separate from transactions**: Assets tracked separately from operational transactions
8. **Schedule E focus**: Rental property income/expense is primary use case; self-employment tax (Schedule SE) not required

## Future Extensions
- Real-time account sync (Plaid API, bank webhooks)
- Machine learning-based transaction categorization
- Multi-entity support (separate ledgers per jurisdiction)
- Cryptocurrency account support
- Investment transaction tracking (cost basis, gains/losses)
- Budget planning and forecasting
- IRPF (Spanish) form generation from transaction mappings
