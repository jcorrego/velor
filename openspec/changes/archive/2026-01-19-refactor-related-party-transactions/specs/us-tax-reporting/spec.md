## MODIFIED Requirements
### Requirement: Owner-Flow Summary Reporting
The system SHALL provide owner-flow summaries for Form 5472 style reporting using category-based transaction queries.

- The system MUST query owner contributions, draws, and related-party transactions via category tax mappings
- Owner-flow summaries MUST aggregate transactions where category has tax_form_code='form_5472'
- Contributions MUST be identified by line_item='owner_contribution'
- Draws MUST be identified by line_item='owner_draw'
- Related-party totals MUST include all transactions mapped to form_5472
- All amounts MUST be converted to USD for Form 5472 reporting

#### Scenario: View owner-flow summary
- **WHEN** a user selects a US filing year
- **THEN** the system SHALL display owner contributions, draws, and related-party totals
- **AND** these amounts SHALL be calculated by querying transactions joined with category_tax_mappings
- **AND** filtering by tax_form_code='form_5472' and respective line_item values

#### Scenario: Query owner contributions via category mapping
- **WHEN** UsTaxReportingService calculates owner contributions for tax year 2024
- **THEN** it SHALL query transactions with categories mapped to form_5472 and line_item='owner_contribution'
- **AND** filter transactions for the specified tax year
- **AND** aggregate and convert amounts to USD

#### Scenario: Query owner draws via category mapping
- **WHEN** UsTaxReportingService calculates owner draws for tax year 2024
- **THEN** it SHALL query transactions with categories mapped to form_5472 and line_item='owner_draw'
- **AND** filter transactions for the specified tax year
- **AND** aggregate and convert amounts to USD
