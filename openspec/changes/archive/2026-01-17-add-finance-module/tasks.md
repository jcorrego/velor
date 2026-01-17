# Tasks: Finance Module Implementation (Pending Only)

## Form Requests (Phase 2)
- [x] Create StoreRelatedPartyTransactionRequest

## Business Logic (Phase 2)
- [x] Create FxRateService:
  - [x] fetchRate(from_currency, to_currency, date): FxRate (ECB primary source)
  - [x] overrideRateForTransaction(transaction_id, override_rate, reason): void
- [x] Create TransactionImportService:
  - [x] parsePDF(file_path, account_id): array of parsed transactions (future: PDF table extraction)
- [x] Create TransactionCategoryService:
  - [x] aggregateByCategory(tax_year, jurisdiction): array
  - [x] computeTaxFormAmounts(tax_year, filing_id): array
  - [x] validateMappings(category_id): bool

## Testing (Phase 2)
- [x] Create unit tests for all models

## Views/Livewire Components (Phase 3)
- [x] Create CategoryMapping Livewire component
- [x] Create FX Rate Override modal (for individual transaction corrections)

## CSV/PDF Import (Phase 3)
- [x] Create transaction categorization helper (regex matching, manual override)

## Documentation (Phase 3)
- [x] Write user guide for CSV import (Banco Santander, Mercury, Bancolombia formats)
- [x] Write user guide for category setup and Schedule E mapping
- [x] Write developer guide for adding new CSV parsers
- [x] Write guide for rental property reporting (Schedule E, IRPF)
- [x] Write guide for FX rate management and overrides
