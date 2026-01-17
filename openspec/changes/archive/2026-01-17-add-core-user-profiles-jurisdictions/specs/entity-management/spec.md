## ADDED Requirements

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
