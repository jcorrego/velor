## ADDED Requirements
### Requirement: Form 5472 Year-End Totals Summary Card
The system SHALL display a Form 5472 summary card that shows the sum of year-end assets and accounts for the selected filing year, grouped by entity within the filing jurisdiction.

#### Scenario: View year-end totals for selected filing year
- **WHEN** a user opens the Form 5472 summary cards for a selected filing year
- **THEN** the system SHALL show totals for year-end assets and accounts
- **AND** totals SHALL be grouped by entity within the filing jurisdiction

#### Scenario: Exclude entities outside the filing jurisdiction
- **WHEN** a user views the Form 5472 year-end totals summary card
- **THEN** entities outside the filing jurisdiction SHALL be excluded from the totals
