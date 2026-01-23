## MODIFIED Requirements
### Requirement: Asset Management
The system SHALL track assets with ownership and acquisition details, deriving jurisdiction from the owning entity, and supporting optional address association.

- Assets MUST inherit jurisdiction from the owning entity; assets SHALL NOT store a separate jurisdiction.
- Assets MAY reference a saved address for location details.
- Asset creation/editing MUST allow selecting an existing address or creating a new address inline.

#### Scenario: Asset inherits jurisdiction from entity
- **WHEN** a user creates or edits an asset
- **THEN** the asset SHALL use the entity’s jurisdiction for reporting and display
- **AND** the asset SHALL NOT store a separate jurisdiction value

#### Scenario: Associate an existing address to an asset
- **WHEN** a user selects a saved address while editing an asset
- **THEN** the asset SHALL store the address association

#### Scenario: Create an address while editing an asset
- **WHEN** a user creates a new address within the asset form
- **THEN** the system SHALL save the address and associate it to the asset
