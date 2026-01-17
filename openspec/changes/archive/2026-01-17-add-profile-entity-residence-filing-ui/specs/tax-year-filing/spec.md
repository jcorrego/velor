## ADDED Requirements

### Requirement: Filing Management UI
The system SHALL provide a UI for users to list, create, and update filings per tax year.

#### Scenario: Create a filing for a tax year
- **WHEN** a user creates a filing for a selected tax year and filing type
- **THEN** the system SHALL validate the selection and persist the filing
- **AND** surface a validation error if a filing already exists for that tax year and form type

#### Scenario: Update filing status
- **WHEN** a user updates the status of a filing
- **THEN** the system SHALL persist the status change and display the updated status in the filings list
