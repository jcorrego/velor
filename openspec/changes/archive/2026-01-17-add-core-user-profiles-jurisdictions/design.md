## Context
This change introduces the foundational data models for a multi-jurisdiction tax tracking platform. The system needs to support:
- Single-user MVP focused on Spain-USA-Colombia jurisdictions
- Multi-currency tracking with per-jurisdiction display preferences
- Residency timeline tracking for determining applicable tax rules per year
- Organization/entity modeling (individuals, LLCs) for ownership and attribution
- Tax year and filing status tracking linked to financial data

**Constraints:**
- Use Laravel 12 conventions with encrypted casts for sensitive data
- PostgreSQL/MySQL compatibility required
- Architecture must support future multi-user/multi-entity expansion
- Follow existing Laravel Boost guidelines

## Goals / Non-Goals

**Goals:**
- Provide jurisdiction-specific user profiles (multiple per user) with name variations and encrypted tax identifiers per jurisdiction
- Track residency history and determine fiscal residence (one country) based on 183-day rule
- Support encrypted storage of tax identifiers (SSN, NIE, NIT, etc.) per jurisdiction profile
- Model jurisdictions with complete metadata for tax calculations
- Support multiple filing types per jurisdiction (e.g., USA: Form 5472, Form 1040)
- Track entity ownership and type (individual vs LLC)
- Link tax year filings to transactions, assets, and documents (foreign keys only; actual linking implemented later)

**Non-Goals:**
- UI implementation (Livewire components come in separate change)
- Tax calculation logic (comes after Finance module)
- Document storage implementation (separate capability)
- Transaction/asset models (part of Finance module)
- Authentication flows (already handled by Fortify)

## Decisions

### Decision: Multiple UserProfiles per User (one per jurisdiction)
**Rationale:** Different jurisdictions require different name variations and tax identifiers. For example, Spain uses NIE with Spanish name format, USA uses SSN with anglicized name. Separate profiles per jurisdiction allow proper form filling per country.

**Key constraint:** user_id + jurisdiction_id must be unique (one profile per user per jurisdiction).

**Alternatives considered:**
- Single profile with JSON for jurisdiction-specific data: Rejected due to reduced queryability and complex validation
- Add all fields to User model: Rejected due to mixing authentication with tax data concerns

### Decision: Encrypted tax IDs using Laravel's encrypted cast
**Rationale:** Sensitive data like SSNs, NIEs must be encrypted at rest. Laravel's `encrypted` cast provides transparent encryption/decryption.

**Implementation:**
```php
protected function casts(): array
{
    return [
        'tax_id_spain' => 'encrypted',
        'tax_id_usa' => 'encrypted',
        'tax_id_colombia' => 'encrypted',
    ];
}
```

### Decision: ResidencyPeriod as separate table with fiscal residence determination
**Rationale:** Users may have multiple residency periods across jurisdictions. Separate table allows querying "where was user resident in year X?" efficiently.

**Fiscal Residence Rule:** For any given tax year, fiscal residence is determined by the jurisdiction where the user spent 183 days or more (183-day rule). Only ONE country can be fiscal residence per year.

**Implementation:** Add method `getFiscalResidenceForYear(int $year): ?Jurisdiction` that:
1. Queries all residency periods overlapping the tax year
2. Calculates days spent in each jurisdiction
3. Returns jurisdiction with ≥183 days, or null if none qualify

**Validation:** Track residency periods accurately; warn if no fiscal residence detected for active tax years.

### Decision: Seed jurisdictions vs dynamic creation
**Rationale:** Spain, USA, Colombia are fixed for MVP. Seeding ensures consistent ISO codes, timezones, and currency metadata.

**Migration path:** Future versions can add UI for jurisdiction management.

### Decision: TaxYear as separate entity
**Rationale:** Tax years are jurisdiction-specific (though all three use calendar year initially). Separate table allows per-year metadata and future support for fiscal years.

### Decision: FilingType entity for multiple forms per jurisdiction
**Rationale:** Each jurisdiction has multiple required forms (e.g., USA: Form 5472 for foreign-owned disregarded entity, Form 1040/1040-NR for personal tax). Need separate tracking per form type.

**Schema:** `filing_types` table with:
- jurisdiction_id (FK)
- code (e.g., "5472", "1040", "1040-NR", "IRPF", "720")
- name (e.g., "Form 5472", "Form 1040")
- description

**Seeded filing types:**
- USA: 5472, 1120 (pro-forma), 1040, 1040-NR, Schedule E
- Spain: IRPF (Modelo 100), Modelo 720
- Colombia: Declaración de Renta

### Decision: Filing entity with status enum per filing type
**Rationale:** Track filing lifecycle per user, per year, per jurisdiction, **per filing type**. Each form has independent status.

**New unique constraint:** (user_id, tax_year_id, filing_type_id)

**Status transitions:**
- Planning → InReview (when user starts review)
- InReview → Filed (when submitted to tax authority)
- Filed is terminal (no further transitions)

### Decision: Entity ownership via user_profile_id
**Rationale:** Each entity (LLC, individual) belongs to a user. Future multi-user support can add pivot table for shared ownership.

## Database Schema

### user_profiles
```
id: bigint (PK)
user_id: bigint (FK -> users)
jurisdiction_id: bigint (FK -> jurisdictions)
name: string (jurisdiction-specific name variation)
tax_id: text (encrypted, jurisdiction-specific)
default_currency: string(3) (ISO 4217, optional override)
display_currencies: json (nullable, per-jurisdiction display preferences)
created_at, updated_at

UNIQUE INDEX: (user_id, jurisdiction_id)
```

**Example:**
- Spain profile: name="Juan Carlos Correa", tax_id="X1234567Y" (NIE), jurisdiction=ESP
- USA profile: name="John Correa", tax_id="123-45-6789" (SSN), jurisdiction=USA
- Colombia profile: name="Juan Carlos Correa", tax_id="123456789" (Cédula), jurisdiction=COL

### residency_periods
```
id: bigint (PK)
user_id: bigint (FK -> users)
jurisdiction_id: bigint (FK -> jurisdictions)
start_date: date
end_date: date (nullable)
is_fiscal_residence: boolean (computed/cached flag for 183+ day rule)
created_at, updated_at

UNIQUE INDEX: (user_id, jurisdiction_id, start_date)
CHECK: end_date IS NULL OR end_date >= start_date
```

**Note:** `is_fiscal_residence` is a computed field to cache whether this jurisdiction qualified as fiscal residence for the period's tax year(s).

### jurisdictions
```
id: bigint (PK)
name: string
iso_code: string(3) UNIQUE (ISO 3166-1 alpha-3)
timezone: string
default_currency: string(3)
tax_year_start_month: tinyint (1 = January)
tax_year_start_day: tinyint (1 = 1st)
created_at, updated_at
```

**Seeded data:**
- Spain: ESP, Europe/Madrid, EUR, 01-01
- USA: USA, America/New_York, USD, 01-01
- Colombia: COL, America/Bogota, COP, 01-01

### entities
```
id: bigint (PK)
user_id: bigint (FK -> users)
jurisdiction_id: bigint (FK -> jurisdictions)
type: enum (Individual, LLC)
name: string
ein_or_tax_id: string (nullable, encrypted)
created_at, updated_at
```

### tax_years
```
id: bigint (PK)
jurisdiction_id: bigint (FK -> jurisdictions)
year: integer
created_at, updated_at

UNIQUE INDEX: (jurisdiction_id, year)
```

### filing_types
```
id: bigint (PK)
jurisdiction_id: bigint (FK -> jurisdictions)
code: string (e.g., "5472", "1040", "IRPF")
name: string (e.g., "Form 5472")
description: text (nullable)
created_at, updated_at

UNIQUE INDEX: (jurisdiction_id, code)
```

**Seeded filing types:**
- USA: {code: "5472", name: "Form 5472"}, {code: "1120", name: "Form 1120 (Pro-forma)"}, {code: "1040", name: "Form 1040"}, {code: "1040-NR", name: "Form 1040-NR"}, {code: "SCHEDULE-E", name: "Schedule E"}
- Spain: {code: "IRPF", name: "Modelo 100 (IRPF)"}, {code: "720", name: "Modelo 720"}
- Colombia: {code: "RENTA", name: "Declaración de Renta"}

### filings
```
id: bigint (PK)
user_id: bigint (FK -> users)
tax_year_id: bigint (FK -> tax_years)
filing_type_id: bigint (FK -> filing_types)
status: enum (Planning, InReview, Filed)
key_metrics: json (nullable, for future use)
created_at, updated_at

UNIQUE INDEX: (user_id, tax_year_id, filing_type_id)
```

## Risks / Trade-offs

### Risk: Residency period overlaps
**Mitigation:** Validation in `StoreResidencyPeriodRequest` checks for overlaps within same jurisdiction. Database constraint ensures uniqueness on (user_profile_id, jurisdiction_id, start_date).

### Risk: Encrypted field performance
**Trade-off:** Encrypted fields cannot be directly queried/indexed. Acceptable for tax IDs (never used in WHERE clauses). Name and currencies remain unencrypted for filtering.

### Risk: Future multi-tenancy complexity
**Mitigation:** User isolation built in from start. All models have user_id or user_profile_id foreign keys. Future multi-tenant mode requires adding tenant_id and RLS policies.

### Trade-off: Per-jurisdiction display currencies
**Implementation:** Store as JSON column `display_currencies` on user_profiles:
```json
{
  "ESP": "EUR",
  "USA": "USD",
  "COL": "COP"
}
```
**Rationale:** Flexible per-jurisdiction preferences without additional tables.

## Migration Plan

### Phase 1: Schema Creation
1. Run migrations in order: jurisdictions → user_profiles → residency_periods → entities → tax_years → filing_types → filings
2. Run JurisdictionSeeder to populate Spain, USA, Colombia
3. Run FilingTypeSeeder to populate form types per jurisdiction
4. Verify foreign key constraints

### Phase 2: Model Setup
1. Create models with relationships
2. Add encrypted casts and enums
3. Write factories and test data generation

### Phase 3: Validation
1. Run `php artisan test --compact` on new feature tests
2. Verify encrypted fields work correctly
3. Test residency period overlap validation

### Rollback
- Drop tables in reverse order: filings, filing_types, tax_years, entities, residency_periods, user_profiles, jurisdictions
- No data migration needed (net-new tables)

## Open Questions

### Q1: Should display_currencies be nullable with fallback to default_currency?
**Answer:** Yes. If NULL or jurisdiction not specified, use default_currency from user profile.

### Q2: How to handle jurisdiction additions beyond Spain/USA/Colombia?
**Answer:** Add via seeder in future changes. Schema supports it. UI for jurisdiction management is non-goal for MVP.

### Q3: Should entities have an "active" status?
**Answer:** Not for MVP. Add in future if needed for archival/historical entity tracking.

### Q4: Format for storing key_metrics JSON in filings?
**Answer:** Leave flexible for now. Structure will emerge as tax calculations are implemented. Use JSON for forward compatibility.
