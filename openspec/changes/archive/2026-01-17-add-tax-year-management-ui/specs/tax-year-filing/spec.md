# Specification: tax-year-filing

## ADDED Requirements

### Requirement: Tax Year Management UI
The system SHALL provide a UI to list and create tax years per jurisdiction.

#### Scenario: Create a tax year in the UI
- **WHEN** a user creates a tax year with jurisdiction and year
- **THEN** the system SHALL persist the tax year record
- **AND** surface a validation error if the year already exists for that jurisdiction

#### Scenario: View available tax years
- **WHEN** a user opens the tax year management screen
- **THEN** the system SHALL list tax years grouped by jurisdiction
- **AND** order years descending within each jurisdiction
