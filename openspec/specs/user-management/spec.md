# user-management Specification

## Purpose
TBD - created by archiving change add-core-user-profiles-jurisdictions. Update Purpose after archive.
## Requirements
### Requirement: Multiple User Profiles Per Jurisdiction
The system SHALL allow users to create multiple profiles (one per jurisdiction) with jurisdiction-specific name variations and encrypted tax identifiers.

#### Scenario: Create Spain-specific profile
- **WHEN** a user creates a profile for Spain with name "Juan Carlos Correa" and tax ID "X1234567Y" (NIE)
- **THEN** the system SHALL store the profile linked to Spain jurisdiction
- **AND** encrypt the tax ID using Laravel's encrypted cast
- **AND** set default currency to EUR if not specified

#### Scenario: Create USA-specific profile with different name
- **WHEN** the same user creates a profile for USA with name "John Correa" and tax ID "123-45-6789" (SSN)
- **THEN** the system SHALL store a second profile linked to USA jurisdiction
- **AND** both profiles SHALL coexist for the same user
- **AND** the USA profile name SHALL be independent of the Spain profile name

#### Scenario: Enforce one profile per user per jurisdiction
- **WHEN** a user attempts to create a second profile for the same jurisdiction
- **THEN** the system SHALL reject the operation with a unique constraint violation error
- **AND** the error SHALL indicate the jurisdiction already has a profile

#### Scenario: Configure per-jurisdiction display currencies
- **WHEN** a user sets display currencies {"ESP": "EUR", "USA": "USD", "COL": "COP"} on their Spain profile
- **THEN** the system SHALL store the configuration as JSON
- **AND** jurisdiction-specific views SHALL display amounts in the configured currency

### Requirement: Fiscal Residence Determination
The system SHALL determine fiscal residence for each tax year based on the 183-day rule, where fiscal residence is the jurisdiction with 183 or more days of presence.

#### Scenario: Calculate fiscal residence for tax year
- **WHEN** a user has residency periods: Spain (Jan 1 - Dec 31, 2025) and USA (Jul 1 - Sep 30, 2025)
- **AND** the system calculates days for 2025
- **THEN** Spain SHALL have 365 days and USA SHALL have 92 days
- **AND** fiscal residence for 2025 SHALL be Spain (≥183 days)

#### Scenario: No fiscal residence when under 183 days
- **WHEN** a user has residency periods totaling less than 183 days in any single jurisdiction for a tax year
- **THEN** the system SHALL return NULL for fiscal residence for that year
- **AND** display a warning that no fiscal residence was determined

#### Scenario: Only one fiscal residence per year
- **WHEN** calculating fiscal residence for any tax year
- **THEN** the system SHALL return at most one jurisdiction
- **AND** prioritize the jurisdiction with the most days when one has ≥183 days

### Requirement: Residency Timeline Tracking
The system SHALL track user residency periods across jurisdictions with start and end dates, supporting multiple concurrent residency periods.

#### Scenario: Add residency period for Spain
- **WHEN** a user adds residency in Spain from 2024-01-01 with no end date
- **THEN** the system SHALL create a residency period linked to Spain jurisdiction
- **AND** the period SHALL be marked as current (end_date is NULL)

#### Scenario: Add overlapping residency period for different jurisdiction
- **WHEN** a user has active residency in Spain starting 2024-01-01
- **AND** adds residency in USA starting 2025-07-01
- **THEN** both residency periods SHALL coexist
- **AND** both SHALL be active if neither has an end date

#### Scenario: Query applicable jurisdictions for tax year
- **WHEN** the system needs to determine jurisdictions where user had residency in 2025
- **THEN** the system SHALL return all jurisdictions where residency period overlaps 2025
- **AND** periods SHALL be ordered by start date ascending

### Requirement: User Profile Management UI
The system SHALL provide a UI for users to list, create, and edit jurisdiction-specific profiles.

#### Scenario: View profiles list
- **WHEN** a user opens the profile management screen
- **THEN** the system SHALL list existing profiles with jurisdiction, display name, and default currency
- **AND** show an empty state when no profiles exist

#### Scenario: Create or edit a profile
- **WHEN** a user submits a new or edited profile for a jurisdiction
- **THEN** the system SHALL validate required fields and persist the profile
- **AND** surface a validation error if a profile already exists for that jurisdiction

### Requirement: Residency Period Management UI
The system SHALL provide a UI for users to manage residency periods across jurisdictions.

#### Scenario: Add a residency period
- **WHEN** a user adds a residency period with jurisdiction, start date, and optional end date
- **THEN** the system SHALL save the period and display it in the residency timeline

#### Scenario: Edit a residency period
- **WHEN** a user updates dates for an existing residency period
- **THEN** the system SHALL persist the changes and reflect them in the timeline
- **AND** allow overlapping residency periods across different jurisdictions

### Requirement: Authentication Entry Experience
The system SHALL present the split authentication layout as the primary entry experience for unauthenticated users, replacing the standalone welcome page.

#### Scenario: Visit root entry point
- **WHEN** an unauthenticated user visits the root URL
- **THEN** the system SHALL render the split authentication layout
- **AND** include the welcome messaging content in the split panel

#### Scenario: Auth panel continuity
- **WHEN** the split authentication layout is displayed
- **THEN** the system SHALL keep existing sign-in and registration flows available in the form panel

