## ADDED Requirements
### Requirement: Year-End Asset and Account Values
The system SHALL allow users to manually enter and edit year-end values for assets and accounts, scoped to a specific entity and tax year, and SHALL compute total assets per entity and tax year from those values.

#### Scenario: Enter a year-end value for an account
- **WHEN** a user selects an entity and tax year and enters a year-end value for a specific account
- **THEN** the system SHALL save the value with the selected entity and tax year
- **AND** the value SHALL be editable by the user

#### Scenario: Enter a year-end value for an asset
- **WHEN** a user enters a year-end value for a real estate asset for a tax year
- **THEN** the system SHALL save the value for that asset and year
- **AND** the system SHALL prevent creating a duplicate year-end value for the same asset and year

#### Scenario: Compute total assets for an entity and tax year
- **WHEN** a user views totals for an entity and tax year
- **THEN** the system SHALL sum the year-end values for all assets and accounts linked to that entity and year
- **AND** the total SHALL be displayed in the reporting currency
