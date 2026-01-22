## ADDED Requirements
### Requirement: Filing Due Date Tracking
The system SHALL store a due date for each filing based on the filing’s tax year and form type.

#### Scenario: Set due date on filing creation
- **WHEN** a user creates a filing for Form 5472 for tax year 2026
- **THEN** the system SHALL store a due date for that filing
- **AND** the due date SHALL be available in the filing detail view

### Requirement: Filing Supplemental Form Data Storage
The system SHALL store per-filing supplemental form data tied to a versioned form schema.

#### Scenario: Store non-transaction form data
- **WHEN** a user saves Form 5472 supplemental fields for a filing
- **THEN** the system SHALL persist that data linked to the filing
- **AND** record the schema version used for validation and display