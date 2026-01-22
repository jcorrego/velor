## REMOVED Requirements
### Requirement: Asset Management
**Reason**: Asset valuations are removed in favor of Year-End Values as the single source of truth for asset/account year-end reporting.
**Migration**: Remove Asset Valuation endpoints/data and use Year-End Values for reporting totals.

The system SHALL track real estate assets with jurisdiction, ownership structure, acquisition cost, and current valuations for tax reporting.

- Users MUST be able to track real estate assets with jurisdiction, acquisition cost, and valuation
- Each asset MUST track: name, type (Residential, Commercial, Land), jurisdiction, ownership structure (Individual/LLC/Partnership)
- Each asset MUST have: acquisition_date, acquisition_cost_in_currency, acquisition_currency
- Each asset MUST support current_valuation tracking: amount, valuation_date, valuation_method (Appraisal, Market Comparable, Tax Assessed)
- Assets MAY support depreciation: method (Straight-line), useful_life_years, annual_depreciation_amount
- Assets MUST link to transactions tagged as rental income or property expenses
- Asset valuations MUST be time-tracked (historical valuations for fair market value determination)

#### Scenario: Real estate with multiple valuations
- **WHEN** user owns Florida apartment (Residential, Miami FL) acquired 2019-03-15 for USD 250,000
- **AND** 2023 appraisal: USD 320,000; 2024 market comparable: USD 335,000
- **THEN** property has multiple historical valuations in original currency
- **AND** annual depreciation: Straight-line, 27.5 years = ~USD 9,091/year
- **AND** converted to EUR for Spanish tax reporting
