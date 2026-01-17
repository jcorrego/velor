## ADDED Requirements

### Requirement: Jurisdiction Data Model
The system SHALL provide predefined jurisdiction records for Spain, USA, and Colombia with complete metadata including ISO codes, timezone, default currency, and tax year definitions.

#### Scenario: Seed Spain jurisdiction
- **WHEN** the jurisdiction seeder runs
- **THEN** Spain SHALL be created with ISO code "ESP"
- **AND** timezone "Europe/Madrid"
- **AND** default currency "EUR"
- **AND** tax year starting January 1st

#### Scenario: Seed USA jurisdiction
- **WHEN** the jurisdiction seeder runs
- **THEN** USA SHALL be created with ISO code "USA"
- **AND** timezone "America/New_York"
- **AND** default currency "USD"
- **AND** tax year starting January 1st

#### Scenario: Seed Colombia jurisdiction
- **WHEN** the jurisdiction seeder runs
- **THEN** Colombia SHALL be created with ISO code "COL"
- **AND** timezone "America/Bogota"
- **AND** default currency "COP"
- **AND** tax year starting January 1st

### Requirement: Jurisdiction Uniqueness
The system SHALL enforce unique ISO codes for jurisdictions.

#### Scenario: Prevent duplicate ISO codes
- **WHEN** attempting to create a jurisdiction with an existing ISO code
- **THEN** the system SHALL reject the operation
- **AND** return a unique constraint violation error

### Requirement: Jurisdiction Relationships
The system SHALL link jurisdictions to residency periods and tax years.

#### Scenario: Query all residency periods for jurisdiction
- **WHEN** querying residency periods for Spain (ESP)
- **THEN** the system SHALL return all residency periods linked to Spain
- **AND** include user profile information via eager loading

#### Scenario: Query tax years for jurisdiction
- **WHEN** querying tax years for USA
- **THEN** the system SHALL return all tax years defined for USA
- **AND** results SHALL be ordered by year descending
