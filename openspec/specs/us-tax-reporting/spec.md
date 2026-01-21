# us-tax-reporting Specification

## Purpose
TBD - created by archiving change add-us-tax-reporting. Update Purpose after archive.
## Requirements
### Requirement: Owner-Flow Summary Reporting
The system SHALL provide owner-flow summaries for Form 5472 style reporting.

#### Scenario: View owner-flow summary
- **WHEN** a user selects a US filing year
- **THEN** the system SHALL display owner contributions, draws, and related-party totals

### Requirement: Schedule E Rental Summary
The system SHALL provide Schedule E style rental summaries in USD per property using category tax mappings instead of name-based filters.

#### Scenario: View property rental summary
- **WHEN** a user opens a US property report
- **THEN** the system SHALL display rental income and expense totals by category
- **AND** only categories mapped to Schedule E are included

### Requirement: Form 1040-NR Summary Reporting
The system SHALL provide Form 1040-NR style summaries in USD using category tax mappings.

#### Scenario: View 1040-NR summary for a filing year
- **WHEN** a user selects a Form 1040-NR filing for a US tax year
- **THEN** the system SHALL display totals grouped by line_item for tax_form_code='form_1040_nr'
- **AND** all amounts SHALL be converted to USD

#### Scenario: Exclude unmapped categories from 1040-NR
- **WHEN** transactions exist that are not mapped to tax_form_code='form_1040_nr'
- **THEN** those transactions SHALL be excluded from the 1040-NR summary

### Requirement: Form 1120 Summary Reporting
The system SHALL provide Form 1120 style summaries in USD using category tax mappings.

#### Scenario: View Form 1120 summary for a filing year
- **WHEN** a user selects a Form 1120 filing for a US tax year
- **THEN** the system SHALL display totals grouped by line_item for tax_form_code='form_1120'
- **AND** all amounts SHALL be converted to USD

#### Scenario: Exclude unmapped categories from Form 1120
- **WHEN** transactions exist that are not mapped to tax_form_code='form_1120'
- **THEN** those transactions SHALL be excluded from the Form 1120 summary

