# colombia-tax-reporting Specification

## Purpose
TBD - created by archiving change add-colombia-tax-reporting. Update Purpose after archive.
## Requirements
### Requirement: Colombia Income Summary
The system SHALL provide income and expense summaries in COP for Colombia reporting.

#### Scenario: View Colombia summary
- **WHEN** a user selects a tax year for Colombia
- **THEN** the system SHALL display income and expense totals in COP

### Requirement: COP-Native Amounts
The system SHALL use the original COP transaction amounts for Colombia reporting totals.

#### Scenario: Include COP transactions
- **WHEN** a Colombia report includes COP-denominated transactions
- **THEN** the system SHALL use their original amounts without currency conversion

### Requirement: COP-Only Reporting UI
The system SHALL display Colombia reporting totals in COP only.

#### Scenario: View COP-only totals
- **WHEN** a user views a Colombia report
- **THEN** the system SHALL display totals only in COP

