## ADDED Requirements
### Requirement: Import Batch Review Queue
The system SHALL store imported data as reviewable batches that require approval before transactions are finalized.

#### Scenario: Queue new import batch
- **WHEN** a user uploads a file or triggers an integration sync
- **THEN** the system SHALL create a Pending batch containing proposed transactions

#### Scenario: Approve import batch
- **WHEN** a user approves a Pending batch
- **THEN** the system SHALL finalize the transactions and mark the batch as Applied

#### Scenario: Reject import batch
- **WHEN** a user rejects a Pending batch
- **THEN** the system SHALL discard proposed transactions and mark the batch as Rejected

### Requirement: Import Mapping Profiles
The system SHALL allow saved column mapping profiles for recurring imports.

#### Scenario: Save mapping profile
- **WHEN** a user maps CSV columns during an import
- **THEN** the system SHALL allow saving the mapping as a reusable profile

#### Scenario: Apply mapping profile
- **WHEN** a user selects a saved profile for a new import
- **THEN** the system SHALL prefill the column mapping from that profile
