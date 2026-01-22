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

### Requirement: Form 5472 Guidance and Section Help
The system SHALL provide Form 5472 help, instructions, and section guidance sourced from the maintained Form 5472 content.

#### Scenario: Display guidance for Form 5472 sections
- **WHEN** a user opens the Form 5472 reporting view
- **THEN** the system SHALL display guidance for each Form 5472 section
- **AND** guidance text SHALL be sourced from the Form 5472 content definitions for the selected tax year

### Requirement: Form 5472 Supplemental Data Capture
The system SHALL allow users to enter and store Form 5472 data that is not derived from transactions, with per-tax-year field variations.

#### Scenario: Capture shareholder and related-party details
- **WHEN** a user edits Form 5472 supplemental data for a filing year
- **THEN** the system SHALL store shareholder and related-party information for that filing
- **AND** fields SHALL follow the Form 5472 schema for the selected tax year

#### Scenario: Preserve data when form fields change by year
- **WHEN** a filing is created for a different tax year
- **THEN** the system SHALL use the Form 5472 schema for that year
- **AND** preserve the filing’s supplemental data with the schema version used

