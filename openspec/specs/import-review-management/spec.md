# import-review-management Specification

## Purpose
TBD - created by archiving change add-transaction-import-review. Update Purpose after archive.
## Requirements
### Requirement: Import Batch Review Queue
The system SHALL store imported data as reviewable batches that require user approval before transactions are finalized in the account.

#### Scenario: Create import batch on file upload
- **WHEN** a user uploads a CSV/PDF file via the import form
- **THEN** the system SHALL parse the file and create an ImportBatch with status Pending
- **AND** the batch SHALL contain proposed_transactions as JSON
- **AND** the batch SHALL store transaction_count for display

#### Scenario: View pending batches in review queue
- **WHEN** a user navigates to Management → Import Review
- **THEN** the system SHALL display all Pending batches sorted by creation date
- **AND** each batch SHALL show account name, transaction count, and creation time

#### Scenario: Approve import batch
- **WHEN** a user selects a Pending batch and clicks Approve
- **THEN** the system SHALL:
  1. Change batch status to Applied
  2. Record approved_by user ID and approved_at timestamp
  3. Create transactions for all proposed transactions
  4. Auto-assign categories using description rules
  5. Finalize all transactions in the account

#### Scenario: Reject import batch
- **WHEN** a user selects a Pending batch and provides rejection reason
- **THEN** the system SHALL:
  1. Change batch status to Rejected
  2. Store rejection_reason text
  3. Discard proposed transactions (no transactions created)
  4. Preserve batch record for audit trail

#### Scenario: Prevent invalid state transitions
- **WHEN** a user attempts to approve a non-Pending batch
- **THEN** the system SHALL display error: "Only pending batches can be approved"
- **WHEN** a user attempts to reject without providing reason
- **THEN** the system SHALL display error: "Please provide a reason for rejection"

### Requirement: Description-Based Category Rules
The system SHALL automatically assign transaction categories based on description patterns during import finalization.

#### Scenario: Apply category rule to imported transaction
- **WHEN** a transaction is created from an approved batch
- **AND** the transaction description matches a category rule pattern
- **THEN** the system SHALL assign the category from the matching rule
- **AND** the match SHALL be case-insensitive
- **AND** the match SHALL check the start of the description

#### Scenario: Load active rules for jurisdiction
- **WHEN** a batch is approved for finalization
- **THEN** the system SHALL load all active DescriptionCategoryRules for the account's jurisdiction
- **AND** rules SHALL be applied in order
- **AND** only rules with is_active=true SHALL be applied

#### Scenario: Pattern matching priority
- **WHEN** multiple rules match a description
- **THEN** the first matching rule (by ID) SHALL be applied
- **AND** no further rules SHALL be evaluated for that transaction

#### Scenario: No match behavior
- **WHEN** a transaction description matches no active rules
- **THEN** the transaction SHALL be created without a category_id
- **AND** the user MAY manually assign a category later

