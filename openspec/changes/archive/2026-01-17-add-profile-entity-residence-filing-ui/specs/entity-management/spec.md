## ADDED Requirements

### Requirement: Entity Management UI
The system SHALL provide a UI for users to list, create, and edit entities.

#### Scenario: View entities list
- **WHEN** a user opens the entity management screen
- **THEN** the system SHALL list entities with name, type, and jurisdiction
- **AND** show an empty state when no entities exist

#### Scenario: Create or edit an entity
- **WHEN** a user submits an entity with a name, type, and jurisdiction
- **THEN** the system SHALL validate required fields and persist the entity
- **AND** allow an optional tax identifier when supported by the entity type
