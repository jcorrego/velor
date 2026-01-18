## ADDED Requirements
### Requirement: Currency Management UI
The system SHALL provide a UI to list, create, and edit currency records used across the application.

#### Scenario: Admin adds a new currency
- **WHEN** an admin provides a valid ISO 4217 code and display name
- **THEN** the currency is created and appears in the currency list

#### Scenario: Duplicate currency code is blocked
- **WHEN** an admin attempts to create a currency with an existing ISO 4217 code
- **THEN** the system rejects the request with a validation error
