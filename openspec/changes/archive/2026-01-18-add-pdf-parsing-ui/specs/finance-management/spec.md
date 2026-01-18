# Specification: Finance Management

## ADDED Requirements

### Requirement: PDF Parsing UI
The system SHALL provide a UI to upload bank statement PDFs, auto-select a supported parser based on the account, and preview parsed transactions before importing.

#### Scenario: Preview PDF import
- **WHEN** a user views the import UI for a supported account
- **AND** uploads a PDF file
- **THEN** the system SHALL display a preview of parsed transactions
- **AND** allow the user to confirm or cancel the import

#### Scenario: PDF parsing errors
- **WHEN** a PDF parser fails to extract transactions
- **THEN** the system SHALL show an error message
- **AND** no transactions SHALL be imported

#### Scenario: Account-based parser selection
- **WHEN** the account is Banco Santander or Bancolombia
- **THEN** the UI SHALL expect a PDF statement file and use the matching PDF parser
- **AND** Mercury accounts SHALL require CSV uploads only
