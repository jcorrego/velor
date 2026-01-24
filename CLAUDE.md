# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build & Development Commands

```bash
# Development (runs Laravel server, queue, logs, and Vite concurrently)
composer run dev

# Build assets for production
npm run build

# Run all tests with linting
composer run test

# Run tests only (without lint)
php artisan test --compact

# Run specific test file
php artisan test --compact tests/Feature/ExampleTest.php

# Run specific test by name
php artisan test --compact --filter=testName

# Format code (run before finalizing changes)
vendor/bin/pint --dirty

# Setup project (fresh install)
composer run setup
```

## Architecture Overview

Velor is a multi-jurisdiction tax assistance platform that organizes financial data across Spain, USA, and Colombia. It centralizes transactions, assets, and accounts in multiple currencies and maps them to tax-relevant categories for reporting.

### Core Domain Hierarchy

```
User
├── UserProfiles (jurisdiction-specific)
├── Entities (Individual, LLC)
│   ├── Accounts → Transactions, YearEndValues
│   ├── Assets → AssetValuations, YearEndValues
│   └── YearEndValues
└── ResidencyPeriods, Filings

Transaction (multi-currency)
├── Account, TransactionCategory
├── OriginalCurrency / ConvertedCurrency
└── Documents (polymorphic)

CategoryTaxMapping (bridges categories to tax forms)
├── TransactionCategory → TaxFormCode + LineItem
```

### Service Layer

Two service locations with distinct purposes:

- `app/Services/` - General utilities (import, categorization, FX, documents)
- `app/Finance/Services/` - Domain-specific tax reporting by jurisdiction

**Key Services:**

| Service | Purpose |
|---------|---------|
| `TransactionImportService` | CSV/PDF parsing with duplicate detection, categorization, FX conversion |
| `TransactionCategorizationService` | Rule-based categorization (manual, regex, database rules) |
| `FxRateService` | Multi-currency conversion with ECB API, caching, historical rates |
| `UsTaxReportingService` | Form 5472, Schedule E, 1040-NR, 1120 aggregations |
| `ColombiaTaxReportingService` / `SpainTaxReportingService` | Jurisdiction-specific reporting |

### Import Review Workflow

All transaction imports require approval through `ImportBatch`:
1. Upload CSV/PDF → Preview with duplicate detection
2. Create `ImportBatch` (status: Pending, proposed_transactions as JSON)
3. Review queue (Livewire component) → Approve or Reject
4. Approved batches finalize transactions

### Parser Architecture

Bank-specific parsers implement contracts:
- `CSVParserContract` / `PDFParserContract`
- Implementations: `SantanderCSVParser`, `MercuryCSVParser`, `BancolombiaCSVParser`, etc.
- Text extraction via `PdfTextExtractor` (SMALOT) or `OcrTextExtractor` (Tesseract)

### Route Organization

| Route File | Purpose |
|------------|---------|
| `routes/web.php` | Views, auth, dashboard, tax form pages (Livewire) |
| `routes/management.php` | Livewire CRUD for profiles, currencies, entities, addresses |
| `routes/finance.php` | RESTful API with Sanctum (accounts, transactions, assets, imports) |

### Key Enums

Located in `app/Enums/`:
- `EntityType` (Individual, LLC)
- `AccountType`, `AssetType`, `TransactionType`
- `Currency` (USD, EUR, COP, GBP, JPY with labels/symbols)
- `TaxFormCode` (form_5472, form_1040_nr, form_1120, schedule_e)
- `ImportBatchStatus` (Pending, Applied, Rejected)

## Multi-Currency Handling

- Always store original currency + amount (`original_amount`, `original_currency_id`)
- Convert to reporting currency (`converted_amount`, `converted_currency_id`)
- Use transaction date for historical FX rates
- Per-transaction FX override available for edge cases

## Database Conventions

- Encrypted: `Entity.ein_or_tax_id`
- JSON columns: `ImportBatch.proposed_transactions`, `Account.integration_metadata`, `Transaction.tags`
- Decimal precision: amounts (2 decimals), FX rates (8 decimals)
- Polymorphic: Documents linked via `documentable` morph

## Tech Stack

- Laravel 12 with Livewire 4 and Volt (single-file components)
- Flux UI (free edition) for components - use `<flux:component />` syntax
- Tailwind CSS v4 (CSS-first with `@theme`, no tailwind.config.js)
- Pest v4 for testing
- Laravel Fortify for auth, Sanctum for API tokens

## Testing Patterns

- Pest closure syntax for tests
- Mock external services via `app()->instance()`
- Factories for model creation
- In-memory SQLite for testing (see `phpunit.xml`)
- Always run `vendor/bin/pint --dirty` before finalizing
