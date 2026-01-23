## ADDED Requirements
### Requirement: Entity Address Association
The system SHALL allow an entity to optionally reference a saved address for location details.

- Entity creation/editing MUST allow selecting an existing address or creating a new address from the entity UI.
- Creating a new address from the entity UI MUST use a modal form.
- Entity address selection MUST be limited to addresses owned by the user.

#### Scenario: Associate an existing address to an entity
- **WHEN** a user selects a saved address while editing an entity
- **THEN** the entity SHALL store the address association

#### Scenario: Create an address while editing an entity
- **WHEN** a user creates a new address from the entity form
- **THEN** the system SHALL save the address and associate it to the entity
- **AND** the address form SHALL appear in a modal
