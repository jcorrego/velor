## ADDED Requirements
### Requirement: Form 5472 Guidance and Section Help
The system SHALL provide Form 5472 help, instructions, and section guidance sourced from the maintained Form 5472 content.

#### Scenario: Display guidance for Form 5472 sections
- **WHEN** a user opens the Form 5472 reporting view
- **THEN** the system SHALL display guidance for each Form 5472 section
- **AND** guidance text SHALL be sourced from the Form 5472 content definitions for the selected tax year

### Requirement: Form 5472 Supplemental Data Capture
The system SHALL allow users to enter and store Form 5472 data that is not derived from transactions, with per-tax-year field variations.

#### Scenario: Capture shareholder and related-party details
- **WHEN** a user edits Form 5472 supplemental data for a filing year
- **THEN** the system SHALL store shareholder and related-party information for that filing
- **AND** fields SHALL follow the Form 5472 schema for the selected tax year

#### Scenario: Preserve data when form fields change by year
- **WHEN** a filing is created for a different tax year
- **THEN** the system SHALL use the Form 5472 schema for that year
- **AND** preserve the filing’s supplemental data with the schema version used