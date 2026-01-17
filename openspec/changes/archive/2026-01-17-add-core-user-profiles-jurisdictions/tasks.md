## 1. Database Schema

### 1.1 Core Migrations
- [ ] 1.1.1 Create `user_profiles` migration with jurisdiction FK and encrypted tax_id per jurisdiction
- [ ] 1.1.2 Create `residency_periods` migration with user FK, country, start/end dates, and fiscal residence flag
- [ ] 1.1.3 Create `jurisdictions` migration with ISO codes, timezone, currencies
- [ ] 1.1.4 Create `entities` migration for individuals/LLCs with user FK
- [ ] 1.1.5 Create `tax_years` migration with year and jurisdiction
- [ ] 1.1.6 Create `filing_types` migration with jurisdiction FK, code, name, description
- [ ] 1.1.7 Create `filings` migration with filing_type FK and status

### 1.2 Enums
- [ ] 1.2.1 Create `EntityType` enum (Individual, LLC)
- [ ] 1.2.2 Create `FilingStatus` enum (Planning, InReview, Filed)

## 2. Models and Relationships

### 2.1 Core Models
- [ ] 2.1.1 Create `UserProfile` model with `user()`, `jurisdiction()`, relationships (one profile per user per jurisdiction)
- [ ] 2.1.2 Create `ResidencyPeriod` model with `user()`, `jurisdiction()` relationships and `getFiscalResidenceForYear()` helper
- [ ] 2.1.3 Create `Jurisdiction` model with `userProfiles()`, `residencyPeriods()`, `taxYears()`, `filingTypes()` relationships
- [ ] 2.1.4 Create `Entity` model with `user()`, `jurisdiction()` relationships
- [ ] 2.1.5 Create `TaxYear` model with `jurisdiction()`, `filings()` relationships
- [ ] 2.1.6 Create `FilingType` model with `jurisdiction()`, `filings()` relationships
- [ ] 2.1.7 Create `Filing` model with `user()`, `taxYear()`, `filingType()` relationships

### 2.2 Model Configuration
- [ ] 2.2.1 Add encrypted casts for sensitive fields (tax IDs)
- [ ] 2.2.2 Add date casts for timeline fields
- [ ] 2.2.3 Add proper return type hints for all relationships
- [ ] 2.2.4 Add fillable/guarded properties

## 3. Factories and Seeders

### 3.1 Factories
- [ ] 3.1.1 Create `UserProfileFactory` with jurisdiction-specific name variations and tax IDs
- [ ] 3.1.2 Create `ResidencyPeriodFactory` for Spain/USA/Colombia scenarios with fiscal residence calculation
- [ ] 3.1.3 Create `JurisdictionFactory` for Spain, USA, Colombia
- [ ] 3.1.4 Create `EntityFactory` for Individual and LLC types
- [ ] 3.1.5 Create `TaxYearFactory` for 2025 onwards
- [ ] 3.1.6 Create `FilingTypeFactory` for different form types
- [ ] 3.1.7 Create `FilingFactory` with various statuses per filing type

### 3.2 Seeders
- [ ] 3.2.1 Create `JurisdictionSeeder` with Spain, USA, Colombia data
- [ ] 3.2.2 Create `FilingTypeSeeder` with form types per jurisdiction (5472, 1040, IRPF, 720, etc.)
- [ ] 3.2.3 Update `DatabaseSeeder` to create complete Spain-USA-Colombia scenario with multiple profiles

## 4. Validation

### 4.1 Form Requests
- [ ] 4.1.1 Create `StoreUserProfileRequest` with validation rules and unique (user_id, jurisdiction_id) check
- [ ] 4.1.2 Create `UpdateUserProfileRequest` with validation rules
- [ ] 4.1.3 Create `StoreResidencyPeriodRequest` with date validation and fiscal residence calculation
- [ ] 4.1.4 Create `StoreEntityRequest` with validation rules
- [ ] 4.1.5 Create `StoreFilingRequest` with validation rules and unique (user_id, tax_year_id, filing_type_id) check

## 5. Tests

### 5.1 Model Tests
- [ ] 5.1.1 Test `UserProfile` relationships, encrypted fields, and one-per-jurisdiction constraint
- [ ] 5.1.2 Test `ResidencyPeriod` date validation and fiscal residence calculation (183-day rule)
- [ ] 5.1.3 Test `Jurisdiction` metadata and relationships
- [ ] 5.1.4 Test `Entity` types and ownership
- [ ] 5.1.5 Test `FilingType` uniqueness per jurisdiction
- [ ] 5.1.6 Test `TaxYear` and `Filing` status transitions per filing type

### 5.2 Feature Tests
- [ ] 5.2.1 Test multiple user profiles creation (Spain, USA, Colombia) with different names and tax IDs
- [ ] 5.2.2 Test fiscal residence determination for various residency scenarios
- [ ] 5.2.3 Test multi-currency configuration per profile
- [ ] 5.2.4 Test entity creation and ownership assignment
- [ ] 5.2.5 Test multiple filings per jurisdiction (e.g., USA Form 5472 + Form 1040)
- [ ] 5.2.6 Test filing lifecycle per filing type (planning → in review → filed)
- [ ] 5.2.7 Test jurisdiction and filing type data integrity (seeded data)

## 6. Documentation
- [ ] 6.1.1 Add PHPDoc blocks for all models
- [ ] 6.1.2 Document encrypted field handling and decryption for form filling
- [ ] 6.1.3 Document fiscal residence logic (183-day rule)
- [ ] 6.1.4 Document filing types per jurisdiction and how to add new form types
