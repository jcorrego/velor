# Specification: Finance Management

## ADDED Requirements

### Requirement: Manual Transaction Entry and Editing
The system SHALL allow users to manually create and edit transactions from the Finance UI.

#### Scenario: Manual transaction creation
- **WHEN** a user enters a transaction with date, account, type, amount, currency, and description
- **THEN** the system SHALL create the transaction and show it in the transaction list
- **AND** the system SHALL validate required fields and ownership

#### Scenario: Access manual transaction form
- **WHEN** a user clicks the Add transaction button in the Finance UI
- **THEN** the system SHALL open the manual transaction form

#### Scenario: Manual transaction editing
- **WHEN** a user edits an existing transaction they own
- **THEN** the system SHALL persist the changes and update the transaction list
- **AND** the system SHALL prevent edits to transactions owned by other users
