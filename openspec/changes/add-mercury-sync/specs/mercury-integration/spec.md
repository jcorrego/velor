## ADDED Requirements
### Requirement: Mercury Read-Only Sync
The system SHALL sync Mercury accounts and transactions in read-only mode.

#### Scenario: Map Mercury accounts
- **WHEN** a user connects Mercury
- **THEN** the system SHALL require mapping Mercury accounts to platform accounts before import

#### Scenario: Import Mercury transactions
- **WHEN** a sync runs
- **THEN** the system SHALL create an import batch with Mercury transactions for review
