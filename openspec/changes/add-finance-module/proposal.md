# Change: Add Finance Module (Accounts, Assets, Transactions, FX Management)

## Why
Tax filing requires comprehensive financial tracking across multiple accounts, jurisdictions, and currencies. Users need to:
- Import transactions from multiple sources (bank statements, CSV exports, PDF statements) into a unified ledger
- Track financial activity across different accounts (bank accounts, investment platforms) denominated in different currencies
- Convert transactions to EUR (primary tax reporting currency) and track valuations in home jurisdiction (Spain)
- Map financial categories to tax concepts (Schedule E for rental income, IRPF sections for Spanish income)
- Track asset valuations and ownership structures for capital gains and investment income
- Link financial data to tax filings with audit trails

## What Changes
- Add **multi-currency support** across accounts and transactions with daily FX rate storage, source preferences (ECB, etc.), and per-transaction override capability
- Add **transaction import** from CSV and PDF formats (Mercury bank statements, Banco Santander extracts, etc.) with intelligent parsing and categorization
- Add **accounts management** supporting Banco Santander, Bancolombia, Mercury, and other sources with account type, entity ownership, and future API sync metadata
- Add **assets management** for real estate (Florida apartment, Colombian properties) tracking jurisdiction, ownership structure, acquisition cost, current valuation, and depreciation
- Add **transactions** with date, description, original currency/amount, account, counterparty, tags, and user-defined categories; auto-normalize to EUR base currency for Spanish tax reporting
- Add **categories and tax concept mappings** allowing users to define categories (e.g., "SPA_Rental_Income", "SPA_Personal_Employment", "Property_Expenses_US") and map them to jurisdiction-specific tax forms and line items
- Add **related-party ledger** tracking owner contributions, draws, personal spending, and reimbursements for compliance tracking

## Impact
- Affected specs: `finance-management` (new capability), `fx-management` (new capability), `tax-form-mapping` (new capability)
- Affected code:
  - New migrations: `currencies`, `fx_rates`, `accounts`, `assets`, `asset_valuations`, `transactions`, `transaction_categories`, `category_tax_mappings`, `related_party_transactions`
  - New models: `Currency`, `FxRate`, `Account`, `Asset`, `AssetValuation`, `Transaction`, `TransactionCategory`, `CategoryTaxMapping`, `RelatedPartyTransaction`
  - New enums: `CurrencyCode`, `AccountType`, `AssetType`, `TransactionType`, `OwnershipStructure`, `RelatedPartyType`
  - Database schema for encrypted account credentials, fx rate caching, asset valuations, transaction reconciliation
- Integration hooks: Account linking (Mercury API, Plaid, OpenBanking), FX rate sourcing (ECB, Fixer.io, OpenExchangeRates)
- **New core concept**: Financial transactions are the source of truth for tax reporting; categories bridge financial reality to tax compliance

## Dependencies
- Requires: `user-management`, `entity-management`, `jurisdiction-management`, `tax-year-filing` (from previous change)
- Enables: Schedule E (rental property) income/expense computation, IRPF (Spanish income tax) section population, capital gains tracking, tax year financial summaries, multi-jurisdictional asset tracking
