# Change: Add Core User Profiles, Jurisdictions, Entities, and Tax Year Filings

## Why
The platform requires foundational data models to support multi-jurisdiction tax tracking. Users need to define their personal identifiers, residency timeline, and default currencies. The system needs jurisdiction metadata (Spain, USA, Colombia) to support tax year definitions and filings. Additionally, we need to model organizations/entities (individuals, LLCs) for ownership tracking, and tax year filings to track status and link to financial data.

## What Changes
- Add user profile management allowing **multiple profiles per user** (one per jurisdiction) with jurisdiction-specific personal identifiers, name variations, and encrypted tax IDs (decryptable for form filling)
- Add residency timeline tracking with **fiscal residence determination** based on 183-day rule (only one fiscal residence country at a time)
- Add multi-currency support at the user level with default base currency and per-jurisdiction display currencies
- Add jurisdiction management for Spain, USA, and Colombia with metadata (ISO codes, timezone, default currency, tax year definitions)
- Add organization/entity management to model individuals and LLCs with future multi-entity support
- Add tax year and **filing type** management supporting **multiple filings per jurisdiction** (e.g., USA: Form 5472 + Form 1040) with status tracking, key metrics placeholders, and links to transactions/assets/documents

## Impact
- Affected specs: `user-management`, `jurisdiction-management`, `entity-management`, `tax-year-filing` (all new capabilities)
- Affected code: 
  - New migrations for `user_profiles`, `residency_periods`, `jurisdictions`, `entities`, `tax_years`, `filing_types`, `filings`
  - New models: `UserProfile`, `ResidencyPeriod`, `Jurisdiction`, `Entity`, `TaxYear`, `FilingType`, `Filing`
  - Database schema changes to support encrypted fields and multiple profiles per user
  - New enums for status, entity types, jurisdiction-related constants, and filing type codes
  - **BREAKING**: User can have multiple profiles (one per jurisdiction) instead of single profile
