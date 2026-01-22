# spain-tax-reporting Specification

## Purpose
TBD - created by archiving change add-spain-tax-reporting. Update Purpose after archive.
## Requirements
### Requirement: IRPF Summary Reporting
The system SHALL provide IRPF income summaries in EUR by category and source.

#### Scenario: View IRPF summary
- **WHEN** a user selects a tax year
- **THEN** the system SHALL display summarized income and expenses by IRPF category

### Requirement: Modelo 720 Asset Dashboard
The system SHALL provide a foreign asset dashboard aligned with Modelo 720 thresholds, based on Year-End Values.

#### Scenario: View foreign asset thresholds
- **WHEN** a user opens the Modelo 720 dashboard
- **AND** Year-End Values exist for the selected entity and tax year
- **THEN** the system SHALL display asset totals by category and threshold status using the Year-End Values

