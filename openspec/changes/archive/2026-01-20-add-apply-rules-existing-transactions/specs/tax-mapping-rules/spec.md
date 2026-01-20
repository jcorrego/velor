## ADDED Requirements
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
