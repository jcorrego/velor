## ADDED Requirements
### Requirement: YNAB Read-Only Sync
The system SHALL sync YNAB budgets, accounts, and transactions in read-only mode.

#### Scenario: Map YNAB accounts
- **WHEN** a user connects YNAB
- **THEN** the system SHALL require mapping YNAB accounts to platform accounts before import

#### Scenario: Import YNAB transactions
- **WHEN** a sync runs
- **THEN** the system SHALL create an import batch with YNAB transactions for review
