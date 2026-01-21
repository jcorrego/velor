# tax-mapping-rules Specification

## Purpose
This specification defines the functional requirements for the tax-mapping rules engine, which applies jurisdiction-specific rules to map transactions to tax concepts and allows users to preview and approve rule outcomes before they are applied.
## Requirements
### Requirement: Rule-Based Tax Mapping
The system SHALL support jurisdiction-specific rules that map transactions to tax concepts.

#### Scenario: Apply mapping rule
- **WHEN** a transaction matches a rule
- **THEN** the system SHALL propose the mapped tax concept during review

### Requirement: Rule Preview and Approval
The system SHALL allow users to preview and approve rule outcomes.

#### Scenario: Preview rule impact
- **WHEN** a user previews a rule
- **THEN** the system SHALL show the transactions that would be affected

### Requirement: Apply Rules to Existing Transactions
The system SHALL allow users to apply a category rule to existing transactions that match the rule but have a different category.

#### Scenario: Preview existing matches
- **WHEN** a user selects "apply to existing transactions" for a rule
- **THEN** the system SHALL display a preview of matching transactions whose current category differs from the rule target

#### Scenario: Apply a single transaction
- **WHEN** a user applies the category change to a previewed transaction
- **THEN** the system SHALL update the transaction category to the rule target
- **AND** the transaction SHALL be removed from the preview list

#### Scenario: Apply all previewed transactions
- **WHEN** a user applies the category change to all previewed transactions
- **THEN** the system SHALL update every previewed transaction category to the rule target
- **AND** the preview list SHALL be cleared

### Requirement: Rule Counterparty Assignment
The system SHALL allow a description category rule to optionally set the transaction counterparty.

#### Scenario: Preview rule counterparty impact
- **WHEN** a user previews a rule with a counterparty value
- **THEN** the system SHALL show the counterparty that will be applied to matching transactions

#### Scenario: Apply rule counterparty to existing transactions
- **WHEN** a user applies a rule with a counterparty value to existing transactions
- **THEN** the system SHALL update the counterparty for those transactions

#### Scenario: Apply rule counterparty to new transactions
- **WHEN** a transaction matches a rule with a counterparty value
- **THEN** the system SHALL set the transaction counterparty to the rule value

