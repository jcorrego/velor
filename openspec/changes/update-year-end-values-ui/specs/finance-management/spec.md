## MODIFIED Requirements
### Requirement: Year-End Asset and Account Values
The system SHALL allow users to manually enter and edit year-end values for assets and accounts directly from the related Accounts and Assets UIs, scoped to a specific entity and tax year, and SHALL compute total assets per entity and tax year from those values.

- The system SHALL provide an edit action from each account and asset view that opens a modal or inline form to manage year-end values by tax year.
- The system SHALL store year-end values without `as_of_date` or `currency`; the value is implicitly the year-end value and uses the related account/asset currency.
- The system SHALL show the most recent year-end value in the account and asset summary views.

#### Scenario: Edit year-end values from an account view
- **WHEN** a user opens an account and selects the year-end values edit action
- **THEN** the system SHALL show a list of all tax years with editable year-end values for that account
- **AND** saving changes SHALL update the values for the selected tax years

#### Scenario: Edit year-end values from an asset view
- **WHEN** a user opens an asset and selects the year-end values edit action
- **THEN** the system SHALL show a list of all tax years with editable year-end values for that asset
- **AND** saving changes SHALL update the values for the selected tax years

#### Scenario: Display latest year-end value in summary
- **WHEN** a user views an account or asset summary
- **THEN** the system SHALL display the most recent year-end value for that item, if one exists
