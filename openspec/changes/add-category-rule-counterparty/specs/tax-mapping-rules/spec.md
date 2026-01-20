## ADDED Requirements
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
