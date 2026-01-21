## ADDED Requirements
### Requirement: Form 1040-NR Summary Reporting
The system SHALL provide Form 1040-NR style summaries in USD using category tax mappings.

#### Scenario: View 1040-NR summary for a filing year
- **WHEN** a user selects a Form 1040-NR filing for a US tax year
- **THEN** the system SHALL display totals grouped by line_item for tax_form_code='form_1040_nr'
- **AND** all amounts SHALL be converted to USD

#### Scenario: Exclude unmapped categories from 1040-NR
- **WHEN** transactions exist that are not mapped to tax_form_code='form_1040_nr'
- **THEN** those transactions SHALL be excluded from the 1040-NR summary