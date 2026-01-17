## ADDED Requirements

### Requirement: Tax Year Definition
The system SHALL create tax year records per jurisdiction starting from 2025, with calendar year alignment for Spain, USA, and Colombia.

#### Scenario: Create tax year for jurisdiction
- **WHEN** creating tax year 2025 for Spain
- **THEN** the system SHALL store the year and jurisdiction linkage
- **AND** enforce uniqueness on (jurisdiction_id, year)

#### Scenario: Prevent duplicate tax years
- **WHEN** attempting to create a duplicate tax year 2025 for Spain
- **THEN** the system SHALL reject the operation
- **AND** return a unique constraint violation error

#### Scenario: Query tax years for jurisdiction
- **WHEN** querying available tax years for USA
- **THEN** the system SHALL return all years defined for USA
- **AND** results SHALL be ordered by year descending

### Requirement: Filing Type Management
The system SHALL provide predefined filing types per jurisdiction representing different tax forms, with each jurisdiction supporting multiple form types.

#### Scenario: Seed USA filing types
- **WHEN** the filing type seeder runs for USA
- **THEN** the system SHALL create filing types: Form 5472, Form 1120 (Pro-forma), Form 1040, Form 1040-NR, Schedule E
- **AND** each SHALL have unique code within USA jurisdiction

#### Scenario: Seed Spain filing types
- **WHEN** the filing type seeder runs for Spain
- **THEN** the system SHALL create filing types: Modelo 100 (IRPF), Modelo 720
- **AND** each SHALL have unique code within Spain jurisdiction

#### Scenario: Query filing types for jurisdiction
- **WHEN** querying filing types for USA
- **THEN** the system SHALL return all form types for USA
- **AND** include code, name, and description for each

### Requirement: Filing Status Tracking Per Form Type
The system SHALL track filing status per user, per tax year, per filing type with three states: Planning, InReview, and Filed.

#### Scenario: Create multiple filings for USA in same year
- **WHEN** a user starts working on 2025 USA filings
- **THEN** the system SHALL allow creating separate filings for Form 5472 and Form 1040
- **AND** each filing SHALL have independent status tracking
- **AND** Form 5472 can be "Filed" while Form 1040 is "Planning"

#### Scenario: Create filing in Planning status
- **WHEN** a user starts working on 2025 Form 5472 filing
- **THEN** the system SHALL create a filing record with status "Planning"
- **AND** link it to the user, tax year 2025, and Form 5472 filing type
- **AND** initialize key_metrics as empty JSON

#### Scenario: Transition from Planning to InReview
- **WHEN** a user updates Form 1040 filing status from "Planning" to "InReview"
- **THEN** the system SHALL update the status
- **AND** record the transition timestamp
- **AND** other filing types SHALL remain unaffected

#### Scenario: Transition to Filed terminal status
- **WHEN** a user marks Form 5472 filing as "Filed"
- **THEN** the system SHALL update the status to "Filed"
- **AND** the status SHALL not allow further transitions

#### Scenario: Prevent duplicate filings for same form type
- **WHEN** attempting to create a second filing for the same user, year, and filing type
- **THEN** the system SHALL reject the operation
- **AND** return a unique constraint violation error

### Requirement: Filing Relationships
The system SHALL link filings to transactions, assets, and documents (via foreign keys, actual data linking implemented in future changes).

#### Scenario: Query filings for user and year
- **WHEN** querying filings for user in 2025
- **THEN** the system SHALL return all filings across jurisdictions and filing types for that year
- **AND** include jurisdiction, tax year, and filing type information

#### Scenario: Filing per jurisdiction and form type visibility
- **WHEN** displaying USA Form 5472 specific tax view
- **THEN** the system SHALL show only the filing for USA Form 5472
- **AND** include filing status and key metrics specific to Form 5472

#### Scenario: Group filings by jurisdiction
- **WHEN** displaying overview of all 2025 filings
- **THEN** the system SHALL group filings by jurisdiction
- **AND** show all form types per jurisdiction with their respective statuses

### Requirement: Key Metrics Placeholder
The system SHALL provide a JSON field for storing filing-type-specific key metrics with future flexibility.

#### Scenario: Store key metrics for Spain IRPF filing
- **WHEN** storing calculated metrics for Spain IRPF (Modelo 100)
- **THEN** the system SHALL accept JSON structure like {"taxable_income_eur": 50000, "foreign_tax_credits": 1200}
- **AND** preserve the structure for retrieval

#### Scenario: Store key metrics for USA Form 5472 filing
- **WHEN** storing Form 5472 related party transactions
- **THEN** the system SHALL accept JSON structure like {"owner_contributions": 100000, "owner_draws": 50000}
- **AND** metrics SHALL be independent from Form 1040 metrics

#### Scenario: Empty key metrics on creation
- **WHEN** creating a new filing
- **THEN** key_metrics SHALL default to NULL or empty JSON object
- **AND** allow updates as calculations are performed
