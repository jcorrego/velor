# Tasks: Finance Module Implementation

## Database & Models (Phase 1)
- [ ] Create Currency enum (USD, EUR, COP, GBP, JPY, etc.) with display names
- [ ] Create migrations:
  - [ ] create_currencies_table
  - [ ] create_fx_rates_table (currency_from, currency_to, rate, rate_date, source)
  - [ ] create_accounts_table (name, type, currency, entity_id, opening_date, closing_date, integration_metadata)
  - [ ] create_assets_table (name, type, jurisdiction_id, ownership_structure, acquisition_date, acquisition_cost, acquisition_currency)
  - [ ] create_asset_valuations_table (asset_id, amount, valuation_date, method)
  - [ ] create_transactions_table (date, amount, currency, account_id, type, description, counterparty, category_id, reconciled_at, import_source)
  - [ ] create_transaction_categories_table (name, jurisdiction_id, entity_id, income_or_expense)
  - [ ] create_category_tax_mappings_table (category_id, tax_form, line_item, jurisdiction)
  - [ ] create_related_party_transactions_table (date, amount, type, owner_id, account_id, description)
  - [ ] create_transaction_imports_table (account_id, file_type, file_name, parsed_count, matched_count, imported_at)

## Model Creation (Phase 1)
- [ ] Create Currency model
- [ ] Create FxRate model with relationships
- [ ] Create Account model (belongs_to Entity)
- [ ] Create Asset model with relationship to AssetValuation
- [ ] Create AssetValuation model
- [ ] Create Transaction model (belongs_to Account, TransactionCategory)
- [ ] Create TransactionCategory model
- [ ] Create CategoryTaxMapping model
- [ ] Create RelatedPartyTransaction model
- [ ] Create TransactionImport model for tracking CSV/PDF imports

## Enums (Phase 1)
- [ ] Create AccountType enum (Checking, Savings, Digital)
- [ ] Create AssetType enum (Residential, Commercial, Land)
- [ ] Create TransactionType enum (Income, Expense, Transfer, Fee)
- [ ] Create OwnershipStructure enum (Individual, LLC, Partnership, Corporation)
- [ ] Create RelatedPartyType enum (OwnerContribution, OwnerDraw, PersonalSpending, Reimbursement)
- [ ] Create TaxFormCode enum (ScheduleE, IRPF, etc. - remove Schedule SE, Form 5472, Schedule C)
- [ ] Create ValuationMethod enum (Appraisal, MarketComparable, TaxAssessed)
- [ ] Create ImportFileType enum (CSV, PDF)

## Factories & Seeders (Phase 1)
- [ ] Create CurrencySeeder (USD, EUR, COP, GBP, JPY)
- [ ] Create AccountFactory with state methods (Banco Santander, Mercury, Bancolombia)
- [ ] Create AssetFactory with state methods (rental properties)
- [ ] Create TransactionFactory with state methods (income, expenses, multi-currency)
- [ ] Create TransactionCategoryFactory (rental income/expense categories)
- [ ] Create FxRateFactory with realistic ECB rate data
- [ ] Create RelatedPartyTransactionFactory
- [ ] Create TransactionImportFactory (track CSV/PDF imports)
- [ ] Update DatabaseSeeder to seed finance module data

## Form Requests (Phase 2)
- [ ] Create CreateAccountRequest
- [ ] Create UpdateAccountRequest
- [ ] Create CreateAssetRequest
- [ ] Create UpdateAssetRequest
- [ ] Create CreateTransactionRequest
- [ ] Create CreateTransactionCategoryRequest
- [ ] Create CreateCategoryTaxMappingRequest
- [ ] Create CreateRelatedPartyTransactionRequest

## Business Logic (Phase 2)
- [ ] Create FxRateService:
  - [ ] fetchRate(from_currency, to_currency, date): FxRate (ECB primary source)
  - [ ] convertAmount(amount, from_currency, to_currency, date): decimal
  - [ ] overrideRate(transaction_id, override_rate, reason): void
- [ ] Create TransactionImportService:
  - [ ] parseCSV(file_path, account_id): array of parsed transactions
  - [ ] parsePDF(file_path, account_id): array of parsed transactions (future: PDF table extraction)
  - [ ] matchTransactions(parsed_transactions, existing_transactions): array of matched/unmatched
  - [ ] importTransactions(parsed_transactions, account_id): int (count imported)
- [ ] Create TransactionCategoryService:
  - [ ] aggregateByCategory(tax_year, jurisdiction): array
  - [ ] computeTaxFormAmounts(tax_year, filing_id): array
  - [ ] validateMappings(category_id): bool
- [ ] Create RentalPropertyService:
  - [ ] computeRentalIncome(asset_id, tax_year): decimal
  - [ ] computeRentalExpenses(asset_id, tax_year): decimal
  - [ ] computeDepreciation(asset_id, tax_year): decimal

## Testing (Phase 2)
- [ ] Create unit tests for all models
- [ ] Create unit tests for FxRateService (ECB rate fetching, caching, overrides)
- [ ] Create unit tests for TransactionImportService (CSV/PDF parsing)
- [ ] Create unit tests for RentalPropertyService (depreciation, income/expense calculation)
- [ ] Create feature tests for CSV import workflow
- [ ] Create feature tests for multi-currency transactions
- [ ] Create feature tests for category mappings (Schedule E, IRPF)
- [ ] Create feature tests for rental property reporting

## UI/Controllers (Phase 3)
- [ ] Create AccountController (index, store, update, destroy)
- [ ] Create AssetController (index, store, update, destroy)
- [ ] Create TransactionController (index, store, destroy)
- [ ] Create TransactionImportController (upload CSV/PDF, preview, confirm import)
- [ ] Create TransactionCategoryController (index, store, update, destroy)
- [ ] Create CategoryTaxMappingController (index, store, destroy)
- [ ] Create ReportController (rental income, expenses, depreciation)

## Views/Livewire Components (Phase 3)
- [ ] Create AccountManagement Livewire component (create, edit, delete accounts)
- [ ] Create AssetManagement Livewire component (rental properties, valuations)
- [ ] Create TransactionList Livewire component (searchable, filterable by category)
- [ ] Create TransactionImportForm Livewire component (CSV/PDF upload, preview, reconciliation)
- [ ] Create CategoryMapping Livewire component (Schedule E, IRPF mappings)
- [ ] Create RentalPropertyReport Livewire component (Schedule E Part I summary)
- [ ] Create FX Rate Override modal (for individual transaction corrections)

## CSV/PDF Import (Phase 3)
- [ ] Implement Banco Santander CSV parser (standard export format)
- [ ] Implement Mercury CSV parser (transaction export format)
- [ ] Implement Bancolombia CSV parser (transaction export format)
- [ ] Implement PDF parser framework (future: extract tables from PDF statements)
- [ ] Create transaction deduplication logic (match by date, amount, counterparty)
- [ ] Create transaction categorization helper (regex matching, manual override)

## API Integration Hooks (Phase 3)
- [ ] Create ECB FX rate sync job (daily rate fetch from ECB API)
- [ ] Create account sync job (Mercury API, future: Plaid, OpenBanking)
- [ ] Create TransactionImportService integration with controllers
- [ ] Create RateLimitMiddleware for API calls (avoid ECB rate limit violations)

## Documentation (Phase 3)
- [ ] Write user guide for CSV import (Banco Santander, Mercury, Bancolombia formats)
- [ ] Write user guide for category setup and Schedule E mapping
- [ ] Write developer guide for adding new CSV parsers
- [ ] Write guide for rental property reporting (Schedule E, IRPF)
- [ ] Write guide for FX rate management and overrides
