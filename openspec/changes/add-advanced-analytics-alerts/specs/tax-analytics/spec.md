## ADDED Requirements
### Requirement: Cross-Jurisdiction Analytics
The system SHALL provide analytics across jurisdictions for a selected tax year.

#### Scenario: View cross-jurisdiction analytics
- **WHEN** a user opens the analytics dashboard
- **THEN** the system SHALL display totals grouped by jurisdiction and category

### Requirement: Data Quality Alerts
The system SHALL alert users about missing data and threshold conditions.

#### Scenario: Missing data alert
- **WHEN** transactions are missing required categories
- **THEN** the system SHALL surface an alert
