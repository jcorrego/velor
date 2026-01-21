## ADDED Requirements
### Requirement: Form 1120 Summary Reporting
The system SHALL provide Form 1120 style summaries in USD using category tax mappings.

#### Scenario: View Form 1120 summary for a filing year
- **WHEN** a user selects a Form 1120 filing for a US tax year
- **THEN** the system SHALL display totals grouped by line_item for tax_form_code='form_1120'
- **AND** all amounts SHALL be converted to USD

#### Scenario: Exclude unmapped categories from Form 1120
- **WHEN** transactions exist that are not mapped to tax_form_code='form_1120'
- **THEN** those transactions SHALL be excluded from the Form 1120 summary