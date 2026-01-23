# entity-management Specification

## Purpose
TBD - created by archiving change add-core-user-profiles-jurisdictions. Update Purpose after archive.
## Requirements
### Requirement: Entity Type Support
The system SHALL support two entity types: Individual and LLC, with future extensibility for additional entity types.

#### Scenario: Create Individual entity
- **WHEN** a user creates an Individual entity with name "John Doe"
- **THEN** the system SHALL create the entity with type "Individual"
- **AND** link it to the user's profile
- **AND** link it to the specified jurisdiction

#### Scenario: Create LLC entity
- **WHEN** a user creates an LLC entity with name "Acme Development LLC" in USA jurisdiction
- **THEN** the system SHALL create the entity with type "LLC"
- **AND** optionally store encrypted EIN or tax ID
- **AND** link it to the USA jurisdiction

### Requirement: Entity Ownership
The system SHALL link entities to user profiles with one-to-many relationship (one user owns multiple entities).

#### Scenario: Query entities owned by user
- **WHEN** querying entities for a user profile
- **THEN** the system SHALL return all entities linked to that user
- **AND** include entity type, name, and jurisdiction information

#### Scenario: Associate entity with jurisdiction
- **WHEN** creating an entity
- **THEN** the system MUST require a jurisdiction assignment
- **AND** the jurisdiction SHALL determine applicable tax rules for that entity

### Requirement: Entity Tax Identifier Storage
The system SHALL securely store entity tax identifiers (EIN, foreign tax IDs) using encryption.

#### Scenario: Store encrypted EIN for LLC
- **WHEN** a user provides an EIN "12-3456789" for their LLC
- **THEN** the system SHALL encrypt the EIN using Laravel's encrypted cast
- **AND** the value SHALL only be decryptable by the application

#### Scenario: Optional tax identifier
- **WHEN** creating an Individual entity without providing a tax ID
- **THEN** the system SHALL allow the entity creation
- **AND** the tax ID field SHALL remain NULL

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

