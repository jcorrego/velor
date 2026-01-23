## ADDED Requirements
### Requirement: Currency Field Formatting
Form 5472 SHALL apply currency input formatting to fields with `"type": "currency"` in the form schema.

#### Scenario: User enters currency amount
- **WHEN** user focuses on a currency field in Form 5472
- **THEN** the field SHALL display with proper currency formatting (commas, decimal places)
- **AND** user input SHALL be formatted as they type

#### Scenario: Form submission preserves numeric values
- **WHEN** user submits Form 5472 with formatted currency fields
- **THEN** the underlying data SHALL store the numeric value without formatting symbols
- **AND** form validation SHALL accept the formatted input

#### Scenario: Currency field displays existing values
- **WHEN** Form 5472 loads with existing currency field values
- **THEN** currency fields SHALL display the values with proper formatting
- **AND** the formatting SHALL match the entity's or form's currency standards