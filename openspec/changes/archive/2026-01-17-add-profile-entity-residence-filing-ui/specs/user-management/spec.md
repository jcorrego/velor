## ADDED Requirements

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
